<?php

declare(strict_types=1);

namespace K911\Swoole\Server;

use Assert\Assertion;
use K911\Swoole\Server\Config\Socket;
use K911\Swoole\Server\Config\Sockets;

/**
 * @todo Create interface and split this class
 * @final
 */
class HttpServerConfiguration
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
        'buffer_output_size' => 'buffer_output_size',
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

    private $sockets;
    private $runningMode;
    private $settings;

    /**
     * @param Sockets $sockets
     * @param string  $runningMode
     * @param array   $settings    settings available:
     *                             - reactor_count (default: number of cpu cores)
     *                             - worker_count (default: 2 * number of cpu cores)
     *                             - serve_static_files (default: false)
     *                             - public_dir (default: '%kernel.root_dir%/public')
     *                             - buffer_output_size (default: '2097152' unit in byte (2MB))
     *
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(Sockets $sockets, string $runningMode = 'process', array $settings = [])
    {
        $this->sockets = $sockets;

        $this->changeRunningMode($runningMode);
        $this->initializeSettings($settings);
    }

    public function changeRunningMode(string $runningMode): void
    {
        Assertion::inArray($runningMode, ['process', 'reactor', 'thread']);

        $this->runningMode = $runningMode;
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

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @throws \Assert\AssertionFailedException
     */
    private function validateSetting(string $key, $value): void
    {
        Assertion::keyExists(self::SWOOLE_HTTP_SERVER_CONFIGURATION, $key, 'There is no configuration mapping for setting "%s".');

        switch ($key) {
            case 'serve_static':
                Assertion::inArray($value, \array_keys(self::SWOOLE_SERVE_STATIC));
                break;
            case 'daemonize':
                Assertion::boolean($value);
                break;
            case 'public_dir':
                Assertion::directory($value, 'Public directory does not exists. Tried "%s".');
                break;
            case 'log_level':
                Assertion::inArray($value, \array_keys(self::SWOOLE_LOG_LEVELS));
                break;
            case 'buffer_output_size':
                Assertion::integer($value, \sprintf('Setting "%s" must be an integer.', $key));
                Assertion::greaterThan($value, 0, 'Buffer output size value cannot be negative or zero, "%s" provided.');
                break;
            case 'reactor_count':
            case 'worker_count':
                Assertion::integer($value, \sprintf('Setting "%s" must be an integer.', $key));
                Assertion::greaterThan($value, 0, 'Count value cannot be negative, "%s" provided.');
                break;
            default:
                return;
        }
    }

    public function isDaemon(): bool
    {
        return isset($this->settings['daemonize']);
    }

    public function hasPidFile(): bool
    {
        return isset($this->settings['pid_file']);
    }

    public function servingStaticContent(): bool
    {
        return isset($this->settings['serve_static']) && 'off' !== $this->settings['serve_static'];
    }

    public function hasPublicDir(): bool
    {
        return isset($this->settings['public_dir']);
    }

    /**
     * @param Socket $socket
     */
    public function changeServerSocket(Socket $socket): void
    {
        $this->sockets->changeServerSocket($socket);
    }

    public function getSockets(): Sockets
    {
        return $this->sockets;
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

    public function isReactorRunningMode(): bool
    {
        return 'reactor' === $this->runningMode;
    }

    public function getRunningMode(): string
    {
        return $this->runningMode;
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

    public function existsPidFile(): bool
    {
        return $this->hasPidFile() && \file_exists($this->getPidFile());
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
        return $this->settings['reactor_count'];
    }

    public function getServerSocket(): Socket
    {
        return $this->sockets->getServerSocket();
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
     * @return array
     *
     * @see \Swoole\Http\Server::set()
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
     * @return int
     *
     * @see getSwooleSettings()
     */
    public function getSwooleLogLevel(): int
    {
        return self::SWOOLE_LOG_LEVELS[$this->settings['log_level']];
    }

    /**
     * @return bool
     *
     * @see getSwooleSettings()
     */
    public function getSwooleEnableStaticHandler(): bool
    {
        return self::SWOOLE_SERVE_STATIC[$this->settings['serve_static']];
    }

    public function getSwooleDocumentRoot(): ?string
    {
        return 'default' === $this->settings['serve_static'] ? $this->settings['public_dir'] : null;
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
