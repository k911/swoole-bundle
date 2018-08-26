<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Server;

use Assert\Assertion;

final class HttpServerConfiguration
{
    /**
     * @todo add more
     *
     * @see https://github.com/swoole/swoole-docs/blob/master/modules/swoole-server/configuration.md
     * @see https://github.com/swoole/swoole-docs/blob/master/modules/swoole-http-server/configuration.md
     */
    private const SWOOLE_HTTP_SERVER_SETTINGS_MAPPING = [
        'reactor_count' => 'reactor_num',
        'worker_count' => 'worker_num',
        'serve_static_files' => 'enable_static_handler',
        'public_dir' => 'document_root',
    ];

    private $host;
    private $port;
    private $settings;

    /**
     * @param string $host
     * @param int    $port
     * @param array  $settings settings available:
     *                         - reactor_count (default: number of cpu cores)
     *                         - worker_count (default: 2 * number of cpu cores)
     *                         - serve_static_files (default: false)
     *                         - public_dir (default: '%kernel.root_dir%/public')
     *
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(string $host = 'localhost', int $port = 9501, array $settings = [])
    {
        $this->changeSocket($host, $port);
        $this->initializeSettings($settings);
    }

    /**
     * @param string $host
     * @param int    $port
     *
     * @throws \Assert\AssertionFailedException
     */
    public function changeSocket(string $host, int $port): void
    {
        Assertion::notBlank($host, 'Host cannot be blank');
        Assertion::greaterThan($port, 0, 'Port cannot be negative');
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @param string $publicDir
     *
     * @throws \Assert\AssertionFailedException
     */
    public function enableServingStaticFiles(string $publicDir): void
    {
        $this->setSettings([
            'serve_static_files' => true,
            'public_dir' => $publicDir,
        ]);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @throws \Assert\AssertionFailedException
     */
    private function validateSetting(string $key, $value): void
    {
        Assertion::keyExists(self::SWOOLE_HTTP_SERVER_SETTINGS_MAPPING, $key, 'There is no configuration mapping for setting "%s".');

        if ('serve_static_files' === $key) {
            Assertion::boolean($value, 'Serve static files setting must be a boolean');
        }

        if ('public_dir' === $key) {
            Assertion::directory($value, 'Public directory does not exists. Tried "%s".');
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

        Assertion::false(isset($settings['serve_static_files']) && !isset($settings['public_dir']), 'Enabling static files serving requires providing "public_dir" setting.');
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

        $this->validateSettings($init);
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
            $this->settings[$name] = $setting;
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
     */
    public function getSwooleSettings(): array
    {
        $swooleSettings = [];
        foreach ($this->settings as $key => $setting) {
            $swooleSettings[self::SWOOLE_HTTP_SERVER_SETTINGS_MAPPING[$key]] = $setting;
        }

        return $swooleSettings;
    }
}
