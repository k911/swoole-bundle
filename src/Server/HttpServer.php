<?php

declare(strict_types=1);

namespace K911\Swoole\Server;

use K911\Swoole\Server\Config\Socket;
use K911\Swoole\Server\Exception\AlreadyAttachedException;
use K911\Swoole\Server\Exception\InvalidServerPortException;
use K911\Swoole\Server\Exception\NoServerAttachedException;
use K911\Swoole\Server\Exception\NotRunningException;
use K911\Swoole\Server\Exception\PortUnavailableException;
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
        $this->alreadyAttached($this->server);

        $defaultSocket = $this->configuration->getServerSocket();
        $this->expectedServerPort($server, $defaultSocket);

        $this->server = $server;

        foreach ($server->ports as $listener) {
            if ($listener->port === $defaultSocket->port()) {
                continue;
            }

            $this->availablePort($this->listeners, $listener->port);
            $this->listeners[$listener->port] = $listener;
        }
    }

    /**
     * @return bool
     */
    public function start(): bool
    {
        return $this->running = $this->getServer()->start();
    }

    /**
     * @throws \Assert\AssertionFailedException
     * @throws NotRunningException
     */
    public function shutdown(): void
    {
        if ($this->server instanceof Server) {
            $this->server->shutdown();
        } elseif ($this->isRunningInBackground()) {
            Process::kill($this->configuration->getPid(), $this->signalTerminate);
        } else {
            throw NotRunningException::create();
        }
    }

    /**
     * @throws \Assert\AssertionFailedException
     * @throws NotRunningException
     */
    public function reload(): void
    {
        if ($this->server instanceof Server) {
            $this->server->reload();
        } elseif ($this->isRunningInBackground()) {
            Process::kill($this->configuration->getPid(), $this->signalReload);
        } else {
            throw NotRunningException::create();
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
        if (null === $this->server) {
            throw NoServerAttachedException::create();
        }

        return $this->server;
    }

    /**
     * @return Listener[]
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }

    private function alreadyAttached(Server $server = null): void
    {
        if (null === $server) {
            return;
        }

        throw AlreadyAttachedException::create();
    }

    private function expectedServerPort(Server $server, Socket $socket): void
    {
        if ($socket->port() === $server->port) {
            return;
        }

        throw InvalidServerPortException::with($server->port, $socket->port());
    }

    private function availablePort(array $listeners, int $port): void
    {
        if (false === \array_key_exists($port, $listeners)) {
            return;
        }

        throw PortUnavailableException::fortPort($port);
    }
}
