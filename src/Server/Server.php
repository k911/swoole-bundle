<?php

declare(strict_types=1);

namespace K911\Swoole\Server;

use Assert\Assertion;
use K911\Swoole\Server\Config\EventCallbacks;
use K911\Swoole\Server\Config\Listeners;
use K911\Swoole\Server\Config\ServerConfig;
use K911\Swoole\Server\Exception\NotRunningException;
use K911\Swoole\Server\Exception\UnexpectedPortException;
use Swoole\Process;
use Throwable;

final class Server implements ServerInterface
{
    public const SERVER_TYPE_TRANSPORT = 'transport';
    public const SERVER_TYPE_HTTP = 'http';
    public const SERVER_TYPE_WEBSOCKET = 'websocket';

    /**
     * @var \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server
     */
    private $swooleServer;

    /**
     * @var array<string[]>|string[]
     */
    private $info;
    private $config;
    private $runningForeground;
    private $signalTerminate;
    private $signalReload;
    private $listeners;
    private $callbacks;

    /**
     * @param \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server $swooleServer
     */
    public function __construct($swooleServer, ServerConfig $config, Listeners $listeners, EventCallbacks $callbacks, bool $running = false)
    {
        $this->setInfo($swooleServer);

        $mainSocket = $listeners->mainSocket();
        if ($mainSocket->port() !== $swooleServer->port) {
            throw UnexpectedPortException::with($swooleServer->port, $mainSocket->port());
        }

        $this->swooleServer = $swooleServer;
        $this->config = $config;
        $this->runningForeground = $running;
        $this->listeners = $listeners;
        $this->callbacks = $callbacks;

        $this->signalTerminate = \defined('SIGTERM') ? (int) \constant('SIGTERM') : 15;
        $this->signalReload = \defined('SIGUSR1') ? (int) \constant('SIGUSR1') : 10;
    }

    public function start(): bool
    {
        return $this->runningForeground = $this->swooleServer->start();
    }

    /**
     * @throws \Assert\AssertionFailedException
     * @throws NotRunningException
     */
    public function shutdown(): void
    {
        if ($this->runningForeground) {
            $this->swooleServer->shutdown();

            return;
        }

        if ($this->runningBackground()) {
            Process::kill($this->config->pid(), $this->signalTerminate);

            return;
        }

        throw NotRunningException::make();
    }

    /**
     * @throws \Assert\AssertionFailedException
     * @throws NotRunningException
     */
    public function reload(): void
    {
        if ($this->runningForeground) {
            $this->swooleServer->reload();

            return;
        }

        if ($this->runningBackground()) {
            Process::kill($this->config->pid(), $this->signalReload);

            return;
        }

        throw NotRunningException::make();
    }

    public function metrics(): array
    {
        if (!$this->runningForeground) {
            throw NotRunningException::make();
        }

        return $this->swooleServer->stats();
    }

    /**
     * @param mixed $data
     */
    public function dispatchTask($data): void
    {
        if (!$this->runningForeground) {
            throw NotRunningException::make();
        }

        $this->swooleServer->task($data);
    }

    public function running(): bool
    {
        return $this->runningForeground || $this->runningBackground();
    }

    public function info(): array
    {
        return $this->info;
    }

    private function runningBackground(): bool
    {
        try {
            return Process::kill($this->config->pid(), 0);
        } catch (Throwable $ex) {
            return false;
        }
    }

    /**
     * @param \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server $swooleServer
     *
     * @throws \Assert\AssertionFailedException
     */
    private function setInfo($swooleServer): void
    {
        $serverClass = \get_class($swooleServer);
        Assertion::inArray($serverClass, [\Swoole\Server::class, \Swoole\Http\Server::class, \Swoole\WebSocket\Server::class]);

        $this->info['class'] = $serverClass;
        $this->info['version'] = \swoole_version();
        $this->info['ssl_supported'] = \defined('SWOOLE_SSL') ? 'true' : 'false';
        $this->info['ssl_versions'] = [
            'sslv2v3' => \defined('SWOOLE_SSLv23_METHOD') ? 'true' : 'false',
            'sslv3' => \defined('SWOOLE_SSLv3_METHOD') ? 'true' : 'false',
            'tlsv1' => \defined('SWOOLE_TLSv1_METHOD') ? 'true' : 'false',
            'tlsv1.1' => \defined('SWOOLE_TLSv1_1_METHOD') ? 'true' : 'false',
            'tlsv1.2' => \defined('SWOOLE_TLSv1_2_METHOD') ? 'true' : 'false',
            'dtlsv1' => \defined('SWOOLE_DTLSv1_METHOD') ? 'true' : 'false',
        ];

        $this->info['http2_supported'] = \defined('SWOOLE_USE_HTTP2') ? 'true' : 'false';

        /** @var string[] $swooleIni */
        $swooleIni = \ini_get_all('swoole', false);
        $this->info['ini'] = $swooleIni;

        switch ($serverClass) {
            case \Swoole\Server::class:
                $this->info['type'] = self::SERVER_TYPE_TRANSPORT;

                break;
            case \Swoole\Http\Server::class:
                $this->info['type'] = self::SERVER_TYPE_HTTP;

                break;
            case \Swoole\WebSocket\Server::class:
                $this->info['type'] = self::SERVER_TYPE_WEBSOCKET;

                break;
            default:
                throw new \DomainException(\sprintf('Unknown server type for class "%s"', $serverClass));
        }
    }
}
