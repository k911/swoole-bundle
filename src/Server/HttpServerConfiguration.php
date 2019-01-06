<?php

declare(strict_types=1);

namespace K911\Swoole\Server;

use Assert\Assertion;
use K911\Swoole\Server\Config\Socket;

final class HttpServerConfiguration
{
    /**
     * @todo add more
     *
     * @see https://github.com/swoole/swoole-docs/blob/master/modules/swoole-server/configuration.md
     * @see https://github.com/swoole/swoole-docs/blob/master/modules/swoole-http-server/configuration.md
     */
    private const SWOOLE_HTTP_SERVER_CONFIGURATION = [
        'reactor_count' => 'reactor_num',
        'daemonize' => 'daemonize',
        'worker_count' => 'worker_num',
        'serve_static' => 'enable_static_handler',
        'public_dir' => 'document_root',
        'log_file' => 'log_file',
        'log_level' => 'log_level',
        'pid_file' => 'pid_file',
    ];

    private const SWOOLE_SERVE_STATIC = [
        'off' => false,
        'advanced' => false,
        'default' => true,
    ];

    private const SWOOLE_LOG_LEVELS = [
        'debug' => SWOOLE_LOG_DEBUG,
        'trace' => SWOOLE_LOG_TRACE,
        'info' => SWOOLE_LOG_INFO,
        'notice' => SWOOLE_LOG_NOTICE,
        'warning' => SWOOLE_LOG_WARNING,
        'error' => SWOOLE_LOG_ERROR,
    ];

    private $defaultSocket;
    private $runningMode;
    private $settings;

    /**
     * @param Socket $defaultSocket
     * @param string $runningMode
     * @param array  $settings      settings available:
     *                              - reactor_count (default: number of cpu cores)
     *                              - worker_count (default: 2 * number of cpu cores)
     *                              - serve_static_files (default: false)
     *                              - public_dir (default: '%kernel.root_dir%/public')
     *
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(Socket $defaultSocket, string $runningMode = 'process', array $settings = [])
    {
        $this->changeRunningMode($runningMode);
        $this->changeDefaultSocket($defaultSocket);
        $this->initializeSettings($settings);
    }

    public function changeRunningMode(string $runningMode): void
    {
        Assertion::inArray($runningMode, ['process', 'reactor', 'thread']);

        $this->runningMode = $runningMode;
    }

    /**
     * @param Socket $socket
     */
    public function changeDefaultSocket(Socket $socket): void
    {
        $this->defaultSocket = $socket;
    }

    /**
     * @param string $publicDir
     *
     * @throws \Assert\AssertionFailedException
     */
    public function enableServingStaticFiles(string $publicDir): void
    {
        $settings = [
            'public_dir' => $publicDir,
        ];

        if ('off' === $this->settings['serve_static']) {
            $settings['serve_static'] = 'default';
        }

        $this->setSettings($settings);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @throws \Assert\AssertionFailedException
     */
    private function validateSetting(string $key, $value): void
    {
        Assertion::keyExists(self::SWOOLE_HTTP_SERVER_CONFIGURATION, $key, 'There is no configuration mapping for setting "%s".');

        if ('serve_static' === $key) {
            Assertion::inArray($value, \array_keys(self::SWOOLE_SERVE_STATIC));
        }

        if ('daemonize' === $key) {
            Assertion::boolean($value);
        }

        if ('public_dir' === $key) {
            Assertion::directory($value, 'Public directory does not exists. Tried "%s".');
        }

        if ('log_level' === $key) {
            Assertion::inArray($value, \array_keys(self::SWOOLE_LOG_LEVELS));
        }

        if (\in_array($key, ['reactor_count', 'worker_count'], true)) {
            Assertion::integer($value, \sprintf('Setting "%s" must be an integer.', $key));
            Assertion::greaterThan($value, 0, 'Count value cannot be negative, "%s" provided.');
        }
    }

    /**
     * @param array $init
     *
     * @throws \Assert\AssertionFailedException
     */
    private function initializeSettings(array $init): void
    {
        $this->settings = [];
        $cpuCores = \swoole_cpu_num();

        if (!isset($init['reactor_count'])) {
            $init['reactor_count'] = $cpuCores;
        }

        if (!isset($init['worker_count'])) {
            $init['worker_count'] = 2 * $cpuCores;
        }

        $this->setSettings($init);
    }

    /**
     * @param array $settings
     *
     * @throws \Assert\AssertionFailedException
     */
    private function setSettings(array $settings): void
    {
        foreach ($settings as $name => $value) {
            if (null !== $value) {
                $this->validateSetting($name, $value);
                $this->settings[$name] = $value;
            }
        }

        Assertion::false($this->isDaemon() && !$this->hasPidFile(), 'Pid file is required when using daemon mode');
        Assertion::false($this->servingStaticContent() && !$this->hasPublicDir(), 'Enabling static files serving requires providing "public_dir" setting.');
    }

    public function getRunningMode(): string
    {
        return $this->runningMode;
    }

    public function hasPublicDir(): bool
    {
        return isset($this->settings['public_dir']);
    }

    public function hasPidFile(): bool
    {
        return isset($this->settings['pid_file']);
    }

    public function servingStaticContent(): bool
    {
        return isset($this->settings['serve_static']) && 'off' !== $this->settings['serve_static'];
    }

    public function existsPidFile(): bool
    {
        return $this->hasPidFile() && \file_exists($this->getPidFile());
    }

    /**
     * @throws \Assert\AssertionFailedException
     *
     * @return int
     */
    public function getPid(): int
    {
        Assertion::true($this->existsPidFile(), 'Could not get pid file. It does not exists or server is not running in background.');

        /** @var string $contents */
        $contents = \file_get_contents($this->getPidFile());
        Assertion::numeric($contents, 'Contents in pid file is not an integer or it is empty');

        return (int) $contents;
    }

    /**
     * @throws \Assert\AssertionFailedException
     *
     * @return string
     */
    public function getPidFile(): string
    {
        Assertion::keyIsset($this->settings, 'pid_file', 'Setting "%s" is not set.');

        return $this->settings['pid_file'];
    }

    public function getWorkerCount(): int
    {
        return $this->settings['worker_count'];
    }

    public function getReactorCount(): int
    {
        return $this->settings['worker_count'];
    }

    public function getDefaultSocket(): Socket
    {
        return $this->defaultSocket;
    }

    /**
     * @throws \Assert\AssertionFailedException
     *
     * @return string|null
     */
    public function getPublicDir(): ?string
    {
        Assertion::keyIsset($this->settings, 'public_dir', 'Setting "%s" is not set.');

        return $this->settings['public_dir'];
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Get settings formatted for swoole http server.
     *
     * @see \Swoole\Http\Server::set()
     *
     * @return array
     *
     * @todo create swoole settings transformer
     */
    public function getSwooleSettings(): array
    {
        $swooleSettings = [];
        foreach ($this->settings as $key => $setting) {
            $swooleSettingKey = self::SWOOLE_HTTP_SERVER_CONFIGURATION[$key];
            $swooleGetter = \sprintf('getSwoole%s', \str_replace('_', '', $swooleSettingKey));
            if (\method_exists($this, $swooleGetter)) {
                $setting = $this->{$swooleGetter}();
            }

            if (null !== $setting) {
                $swooleSettings[$swooleSettingKey] = $setting;
            }
        }

        return $swooleSettings;
    }

    /**
     * @see getSwooleSettings()
     *
     * @return int
     */
    public function getSwooleLogLevel(): int
    {
        return self::SWOOLE_LOG_LEVELS[$this->settings['log_level']];
    }

    /**
     * @see getSwooleSettings()
     *
     * @return bool
     */
    public function getSwooleEnableStaticHandler(): bool
    {
        return self::SWOOLE_SERVE_STATIC[$this->settings['serve_static']];
    }

    public function getSwooleDocumentRoot(): ?string
    {
        return 'default' === $this->settings['serve_static'] ? $this->settings['public_dir'] : null;
    }

    public function isDaemon(): bool
    {
        return isset($this->settings['daemonize']);
    }

    /**
     * @param string|null $pidFile
     *
     * @throws \Assert\AssertionFailedException
     */
    public function daemonize(?string $pidFile = null): void
    {
        $settings = ['daemonize' => true];

        if (null !== $pidFile) {
            $settings['pid_file'] = $pidFile;
        }

        $this->setSettings($settings);
    }
}
