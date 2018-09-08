<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Server;

use Assert\Assertion;
use DomainException;
use InvalidArgumentException;

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

    private const SWOOLE_RUNNING_MODE = [
        'reactor' => SWOOLE_BASE,
        'thread' => SWOOLE_THREAD,
        'process' => SWOOLE_PROCESS,
    ];

    private const SWOOLE_SOCKET_TYPE = [
        'tcp' => SWOOLE_SOCK_TCP,
        'tcp_ipv6' => SWOOLE_SOCK_TCP6,
        'udp' => SWOOLE_SOCK_UDP,
        'udp_ipv6' => SWOOLE_SOCK_UDP6,
        'unix_dgram' => SWOOLE_SOCK_UNIX_DGRAM,
        'unix_stream' => SWOOLE_SOCK_UNIX_STREAM,
    ];

    private const PORT_MAX_VALUE = 65535;
    private const PORT_MIN_VALUE = 0;

    /**
     * @see https://github.com/swoole/swoole-docs/blob/master/modules/swoole-server/methods/construct.md#parameter
     *
     * @var string
     * @var int    $port
     * @var string $runningMode
     * @var string $socketType
     * @var bool   $sslEnabled
     */
    private $host;
    private $port;
    private $runningMode;
    private $socketType;
    private $sslEnabled;

    // Container for SWOOLE_HTTP_SERVER_CONFIGURATION values
    private $settings;

    /**
     * @param string $host
     * @param int    $port
     * @param string $runningMode
     * @param string $socketType
     * @param bool   $sslEnabled
     * @param array  $settings    settings available:
     *                            - reactor_count (default: number of cpu cores)
     *                            - worker_count (default: 2 * number of cpu cores)
     *                            - serve_static_files (default: false)
     *                            - public_dir (default: '%kernel.root_dir%/public')
     *
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(
        string $host = 'localhost',
        int $port = 9501,
        string $runningMode = 'process',
        string $socketType = 'sock_tcp',
        bool $sslEnabled = false,
        array $settings = []
    ) {
        $this->initializeSettings($settings);
        $this->changeSocket($host, $port, $runningMode, $socketType, $sslEnabled);
    }

    /**
     * @param string      $host
     * @param int         $port
     * @param null|string $runningMode
     * @param null|string $socketType
     * @param bool|null   $sslEnabled
     *
     * @throws \Assert\AssertionFailedException
     */
    public function changeSocket(string $host, int $port, ?string $runningMode = null, ?string $socketType = null, ?bool $sslEnabled = null): void
    {
        if (null === $runningMode) {
            $runningMode = $this->runningMode ?? 'process';
        }

        if (null === $socketType) {
            $socketType = $this->socketType ?? 'tcp';
        }

        if (null === $sslEnabled) {
            $sslEnabled = $this->sslEnabled ?? false;
        }

        Assertion::notBlank($host, 'Host cannot be blank.');
        Assertion::between($port, self::PORT_MIN_VALUE, self::PORT_MAX_VALUE, 'Provided port value "%s" is not between 0 and 65535.');
        Assertion::inArray($runningMode, \array_keys(self::SWOOLE_RUNNING_MODE));
        Assertion::inArray($socketType, \array_keys(self::SWOOLE_SOCKET_TYPE));

        if ($sslEnabled) {
            Assertion::defined('SWOOLE_SSL', 'Swoole SSL support is disabled. You must install php extension with SSL support enabled.');
        }

        $this->host = $host;
        $this->runningMode = $runningMode;
        $this->port = $port;
        $this->socketType = $socketType;
        $this->sslEnabled = $sslEnabled;
    }

    public function changePort(int $port): void
    {
        if (0 !== $this->port || $port <= self::PORT_MIN_VALUE || $port > self::PORT_MAX_VALUE) {
            throw new DomainException('Method changePort() can be used directly, only if port originally was set to 0, which means random available port. Use changeSocket() instead.');
        }

        $this->port = $port;
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
     * @param array $settings
     *
     * @throws \Assert\AssertionFailedException
     */
    private function validateSettings(array $settings): void
    {
        foreach ($settings as $name => $value) {
            $this->validateSetting($name, $value);
        }

        Assertion::false(isset($settings['serve_static']) && 'off' !== $settings['serve_static'] && !isset($settings['public_dir']), 'Enabling static files serving requires providing "public_dir" setting.');
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
        $this->validateSettings($settings);
        foreach ($settings as $name => $setting) {
            if (null !== $setting) {
                $this->settings[$name] = $setting;
            }
        }
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getSwooleRunningMode(): int
    {
        return self::SWOOLE_RUNNING_MODE[$this->runningMode];
    }

    /**
     * @return int
     */
    public function getSwooleSocketType(): int
    {
        $type = self::SWOOLE_SOCKET_TYPE[$this->socketType];

        if (!$this->isSslEnabled()) {
            return $type;
        }

        if (!\defined('SWOOLE_SSL')) {
            throw new InvalidArgumentException('Swoole SSL support is disabled. You must install php extension with SSL support enabled.');
        }

        return $type | SWOOLE_SSL;
    }

    public function isSslEnabled(): bool
    {
        return $this->sslEnabled;
    }

    public function hasPublicDir(): bool
    {
        return isset($this->settings['public_dir']);
    }

    public function getWorkerCount(): int
    {
        return $this->settings['worker_count'];
    }

    public function getReactorCount(): int
    {
        return $this->settings['worker_count'];
    }

    /**
     * @throws \Assert\AssertionFailedException
     *
     * @return null|string
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
}
