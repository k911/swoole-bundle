<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Config;

use Assert\Assertion;

final class ServerConfig
{
    public const RUNNING_MODE_PROCESS = 'process';
    public const RUNNING_MODE_REACTOR = 'reactor';

    public const CONFIG_REACTOR_COUNT = 'reactor_count';
    public const CONFIG_WORKER_COUNT = 'worker_count';
    public const CONFIG_TASK_WORKER_COUNT = 'task_worker_count';
    public const CONFIG_PUBLIC_DIR = 'public_dir';
    public const CONFIG_DAEMON_MODE = 'daemon_mode';
    public const CONFIG_STATIC_HANDLER = 'static_handler';
    public const CONFIG_LOG_LEVEL = 'log_level';
    public const CONFIG_LOG_FILE = 'log_file';
    public const CONFIG_PID_FILE = 'pid_file';
    public const CONFIG_BUFFER_OUTPUT_SIZE = 'buffer_output_size';
    public const CONFIG_PACKAGE_MAX_LENGTH = 'package_max_length';

    public const LOG_LEVEL_DEBUG = 'debug';
    public const LOG_LEVEL_TRACE = 'trace';
    public const LOG_LEVEL_INFO = 'info';
    public const LOG_LEVEL_NOTICE = 'notice';
    public const LOG_LEVEL_WARNING = 'warning';
    public const LOG_LEVEL_ERROR = 'error';
    public const LOG_LEVEL_NONE = 'none';

    public const PROCESS_COUNT_AUTO = 'auto';

    private const SWOOLE_RUNNING_MODES = [
        self::RUNNING_MODE_PROCESS => \SWOOLE_PROCESS,
        self::RUNNING_MODE_REACTOR => \SWOOLE_BASE,
    ];

    private const RENAMED_CONFIGS = [
        self::CONFIG_REACTOR_COUNT => 'reactor_num',
        self::CONFIG_WORKER_COUNT => 'worker_num',
        self::CONFIG_TASK_WORKER_COUNT => 'task_worker_num',
        self::CONFIG_PUBLIC_DIR => 'document_root',
        self::CONFIG_DAEMON_MODE => 'daemonize',
        self::CONFIG_STATIC_HANDLER => 'enable_static_handler',
    ];

    private const SWOOLE_LOG_LEVELS = [
        self::LOG_LEVEL_DEBUG => SWOOLE_LOG_DEBUG,
        self::LOG_LEVEL_TRACE => SWOOLE_LOG_TRACE,
        self::LOG_LEVEL_INFO => SWOOLE_LOG_INFO,
        self::LOG_LEVEL_NOTICE => SWOOLE_LOG_NOTICE,
        self::LOG_LEVEL_WARNING => SWOOLE_LOG_WARNING,
        self::LOG_LEVEL_ERROR => SWOOLE_LOG_ERROR,
        self::LOG_LEVEL_NONE => SWOOLE_LOG_NONE,
    ];

    /**
     * @var array<string,array<callable>>
     */
    private $configHandlersMap;

    /**
     * @var array<string,scalar>
     */
    private $config;

    /**
     * @var int number of CPU cores inferred by swoole
     */
    private $systemCpuCoresCount;

    /**
     * @var string
     */
    private $runningMode;

    public function __construct(string $runningMode = self::RUNNING_MODE_PROCESS, array $config = [])
    {
        $this->configHandlersMap = [
            self::CONFIG_PACKAGE_MAX_LENGTH => [
                [$this, 'processIntGreaterThanZero'],
            ],
            self::CONFIG_BUFFER_OUTPUT_SIZE => [
                [$this, 'processIntGreaterThanZero'],
            ],
            self::CONFIG_STATIC_HANDLER => [
                [$this, 'validateBoolean'],
            ],
            self::CONFIG_DAEMON_MODE => [
                [$this, 'validateBoolean'],
            ],
            self::CONFIG_LOG_LEVEL => [
                [$this, 'validateLogLevel'],
            ],
            self::CONFIG_PUBLIC_DIR => [
                [$this, 'validateDirectory'],
            ],
            self::CONFIG_WORKER_COUNT => [
                [$this, 'processCountHandler'],
            ],
            self::CONFIG_REACTOR_COUNT => [
                [$this, 'processCountHandler'],
            ],
            self::CONFIG_TASK_WORKER_COUNT => [
                [$this, 'processCountHandler'],
            ],
            // TODO: validateParentDirectoryWritable($filePath) : log_file, pid_file
        ];
        $this->systemCpuCoresCount = \swoole_cpu_num();
        $this->config = [];

        Assertion::keyExists(self::SWOOLE_RUNNING_MODES, $runningMode);
        $this->runningMode = $runningMode;

        $this->add($config);
    }

    public function add(array $config): void
    {
        $errorBag = [];
        $handledConfig = $this->config;

        foreach ($config as $configKey => $configValue) {
            $configKey = \mb_strtolower($configKey);

            if (\array_key_exists($configKey, $this->configHandlersMap)) {
                /** @var callable $configHandler */
                foreach ($this->configHandlersMap[$configKey] as $configHandler) {
                    try {
                        $configValue = $configHandler($configValue, $configKey) ?? $configValue;
                    } catch (\Throwable $err) {
                        $errorBag[] = $err;
                    }
                }
            }

            $handledConfig[$configKey] = $configValue;
        }

        try {
            $handledConfig = $this->setDefaultsGlobalConfigHandler($handledConfig);
        } catch (\Throwable $err) {
            $errorBag[] = $err;
        }

        try {
            $this->validateGlobalConfig($handledConfig);
        } catch (\Throwable $err) {
            $errorBag[] = $err;
        }

        if (!empty($errorBag)) {
            throw new \RuntimeException(\sprintf('Configuration errors have occurred: %s', \implode(', ', \array_map(function (\Throwable $err): string { return $err->getMessage().\sprintf('[%s:%d]', $err->getFile(), $err->getLine()); }, $errorBag))));
        }

        $this->config = $handledConfig;
    }

    public function runningMode(): string
    {
        return $this->runningMode;
    }

    public function reactorEnabled(): bool
    {
        return self::RUNNING_MODE_REACTOR === $this->runningMode;
    }

    public function swooleRunningMode(): int
    {
        return self::SWOOLE_RUNNING_MODES[$this->runningMode];
    }

    /**
     * Return config optimized for swoole server instance.
     */
    public function swooleConfig(): array
    {
        return $this->applySwooleRenames($this->config);
    }

    public function daemonModeEnabled(): bool
    {
        /** @var bool $daemonModeEnabled */
        $daemonModeEnabled = $this->config[self::CONFIG_DAEMON_MODE];

        return $daemonModeEnabled;
    }

    public function staticHandlerEnabled(): bool
    {
        /** @var bool $staticHandlerEnabled */
        $staticHandlerEnabled = $this->config[self::CONFIG_STATIC_HANDLER];

        return $staticHandlerEnabled;
    }

    public function enableReactorMode(): void
    {
        $this->runningMode = self::RUNNING_MODE_REACTOR;
    }

    public function enableProcessMode(): void
    {
        $this->runningMode = self::RUNNING_MODE_PROCESS;
    }

    public function hasPidFile(): bool
    {
        return isset($this->config[self::CONFIG_PID_FILE]);
    }

    public function hasPublicDir(): bool
    {
        return !empty($this->config[self::CONFIG_PUBLIC_DIR]);
    }

    public function pidFile(): string
    {
        /** @var string $pidFile */
        $pidFile = $this->config[self::CONFIG_PID_FILE];

        return $pidFile;
    }

    public function existsPidFile(): bool
    {
        return $this->hasPidFile() && \file_exists($this->pidFile());
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function pid(): int
    {
        Assertion::true($this->existsPidFile(), 'Could not get pid file. It does not exist or server is not running in background (daemon mode)');

        /** @var string $contents */
        $contents = \file_get_contents($this->pidFile());
        Assertion::numeric($contents, 'Contents in pid file is not an integer or it is empty');

        return (int) $contents;
    }

    public function config(): array
    {
        return $this->config;
    }

    private function applySwooleRenames(array $config): array
    {
        $renamed = [];
        $unchanged = [];
        foreach ($config as $key => $value) {
            if (\array_key_exists($key, self::RENAMED_CONFIGS)) {
                $renamed[self::RENAMED_CONFIGS[$key]] = $value;
            } else {
                $unchanged[$key] = $value;
            }
        }

        return \array_merge($renamed, $unchanged);
    }

    /**
     * @param mixed $value
     */
    private function validateLogLevel($value, string $key): void
    {
        Assertion::keyExists(self::SWOOLE_LOG_LEVELS, $value, \sprintf('Provided log level "%%s" is incorrect, available values for config "%s" are: %s', $key, \implode(', ', \array_keys(self::SWOOLE_LOG_LEVELS))));
    }

    /**
     * @param mixed $value
     *
     * @throws \Assert\AssertionFailedException
     */
    private function validateDirectory($value, string $key): void
    {
        Assertion::directory($value, \sprintf('Config "%s" must be an existing directory. Path "%%s" is not a directory.', $key));
    }

    /**
     * @param mixed $value
     *
     * @throws \Assert\AssertionFailedException
     */
    private function validateBoolean($value, string $key): void
    {
        Assertion::boolean($value, \sprintf('Config "%s" must be a boolean, "%%s" given', $key));
    }

    /**
     * @param mixed $value
     *
     * @throws \Assert\AssertionFailedException
     *
     * @return int|string Int which is greater than 0 or string 'auto'
     */
    private function processCountHandler($value, string $key)
    {
        if (self::PROCESS_COUNT_AUTO === $value) {
            return self::PROCESS_COUNT_AUTO;
        }

        return $this->processIntGreaterThanZero($value, $key);
    }

    /**
     * @param mixed $value
     *
     * @throws \Assert\AssertionFailedException
     */
    private function processIntGreaterThanZero($value, string $key): int
    {
        Assertion::numeric($value, \sprintf('Config "%s" must be an integer, "%%s" given', $key));
        $count = (int) $value;
        Assertion::greaterThan($value, 0, \sprintf('Number of "%s" must be greater than 0, "%%s" given', $key));

        return $count;
    }

    private function setDefaultsGlobalConfigHandler(array $config): array
    {
        if (!isset($config[self::CONFIG_DAEMON_MODE])) {
            $config[self::CONFIG_DAEMON_MODE] = false;
        }

        if (!isset($config[self::CONFIG_STATIC_HANDLER])) {
            $config[self::CONFIG_STATIC_HANDLER] = false;
        }

        if (!isset($config[self::CONFIG_REACTOR_COUNT]) || self::PROCESS_COUNT_AUTO === $config[self::CONFIG_REACTOR_COUNT]) {
            $config[self::CONFIG_REACTOR_COUNT] = $this->systemCpuCoresCount;
        }

        if (!isset($config[self::CONFIG_WORKER_COUNT]) || self::PROCESS_COUNT_AUTO === $config[self::CONFIG_WORKER_COUNT]) {
            $config[self::CONFIG_WORKER_COUNT] = 2 * $this->systemCpuCoresCount;
        }

        if (isset($config[self::CONFIG_TASK_WORKER_COUNT]) && self::PROCESS_COUNT_AUTO === $config[self::CONFIG_TASK_WORKER_COUNT]) {
            $config[self::CONFIG_TASK_WORKER_COUNT] = $this->systemCpuCoresCount;
        }

        return $config;
    }

    private function validateGlobalConfig(array $config): void
    {
        if (true === $config[self::CONFIG_DAEMON_MODE]) {
            Assertion::keyIsset($config, self::CONFIG_PID_FILE, 'Pid file is required when using daemon mode');
        }

        if (true === $config[self::CONFIG_STATIC_HANDLER]) {
            Assertion::keyIsset($config, self::CONFIG_PUBLIC_DIR, \sprintf('Enabling static files serving requires providing "%s" config', self::CONFIG_PUBLIC_DIR));
        }
    }
}
