<?php

declare(strict_types=1);

namespace K911\Swoole\Server;

use K911\Swoole\Server\Exception\IllegalInitializationException;
use K911\Swoole\Server\Exception\NotRunningException;
use K911\Swoole\Server\Exception\PortUnavailableException;
use K911\Swoole\Server\Exception\UnexpectedPortException;
use K911\Swoole\Server\Exception\UninitializedException;
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
        $this->assertNotInitialized();
        $this->assertInstanceConfiguredProperly($server);

        $this->server = $server;
        $defaultSocketPort = $this->configuration->getServerSocket()
            ->port();

        foreach ($server->ports as $listener) {
            if ($listener->port === $defaultSocketPort) {
                continue;
            }

            $this->assertPortAvailable($this->listeners, $listener->port);
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
            throw NotRunningException::make();
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
            throw NotRunningException::make();
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
            throw UninitializedException::make();
        }

        return $this->server;
    }

    public function dispatchTask($data): void
    {
        $this->getServer()->task($data);
    }

    /**
     * @return Listener[]
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }

    private function assertNotInitialized(): void
    {
        if (null === $this->server) {
            return;
        }

        throw IllegalInitializationException::make();
    }

    private function assertInstanceConfiguredProperly(Server $server): void
    {
        $defaultSocket = $this->configuration->getServerSocket();

        if ($defaultSocket->port() !== $server->port) {
            throw UnexpectedPortException::with($server->port, $defaultSocket->port());
        }
    }

    private function assertPortAvailable(array $listeners, int $port): void
    {
        if (false === \array_key_exists($port, $listeners)) {
            return;
        }

        throw PortUnavailableException::fortPort($port);
    }
}
