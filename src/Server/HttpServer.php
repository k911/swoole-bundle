<?php

declare(strict_types=1);

namespace K911\Swoole\Server;

use Assert\Assertion;
use RuntimeException;
use Swoole\Http\Server;
use Swoole\Process;
use Swoole\Server\Port as Listener;
use Throwable;

final class HttpServer
{
    private $running;
    private $configuration;
    /**
     * @var Server|null
     */
    private $server;

    /**
     * @var Listener[]
     */
    private $listeners = [];
    private $signalTerminate;
    private $signalReload;

    public function __construct(HttpServerConfiguration $configuration, bool $running = false)
    {
        $this->signalTerminate = \defined('SIGTERM') ? (int) \constant('SIGTERM') : 15;
        $this->signalReload = \defined('SIGUSR1') ? (int) \constant('SIGUSR1') : 10;

        $this->running = $running;
        $this->configuration = $configuration;
    }

    /**
     * Attach already configured Swoole HTTP Server instance.
     *
     * @param Server $server
     */
    public function attach(Server $server): void
    {
        Assertion::null($this->server, 'Swoole HTTP Server has been already attached. Cannot attach server or listeners multiple times.');

        $defaultSocket = $this->configuration->getServerSocket();
        Assertion::eq($server->port, $defaultSocket->port(), 'Attached Swoole HTTP Server has different port (%s), than expected (%s).');

        $this->server = $server;

        foreach ($server->ports as $listener) {
            if ($listener->port === $defaultSocket->port()) {
                continue;
            }

            Assertion::keyNotExists($this->listeners, $listener->port, 'Cannot attach listener on port (%s). It is already registered.');
            $this->listeners[$listener->port] = $listener;
        }
    }

    /**
     * @throws \Assert\AssertionFailedException
     *
     * @return bool
     */
    public function start(): bool
    {
        return $this->running = $this->getServer()->start();
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function shutdown(): void
    {
        if ($this->server instanceof Server) {
            $this->server->shutdown();
        } elseif ($this->isRunningInBackground()) {
            Process::kill($this->configuration->getPid(), $this->signalTerminate);
        } else {
            throw new RuntimeException('Swoole HTTP Server has not been running.');
        }
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function reload(): void
    {
        if ($this->server instanceof Server) {
            $this->server->reload();
        } elseif ($this->isRunningInBackground()) {
            Process::kill($this->configuration->getPid(), $this->signalReload);
        } else {
            throw new RuntimeException('Swoole HTTP Server has not been running.');
        }
    }

    public function metrics(): array
    {
        return $this->getServer()->stats();
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running || $this->isRunningInBackground();
    }

    /**
     * @return bool
     */
    private function isRunningInBackground(): bool
    {
        try {
            return Process::kill($this->configuration->getPid(), 0);
        } catch (Throwable $ex) {
            return false;
        }
    }

    public function getServer(): Server
    {
        Assertion::isInstanceOf($this->server, Server::class, 'Swoole HTTP Server has not been setup yet. Please use attach method.');

        return $this->server;
    }

    /**
     * @return Listener[]
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }
}
