<?php

declare(strict_types=1);

namespace K911\Swoole\Server;

use Assert\Assertion;
use RuntimeException;
use Swoole\Http\Server;
use Swoole\Process;
use Throwable;

final class HttpServer
{
    /**
     * @var Server|null
     */
    private $server;

    private $running;
    private $configuration;
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
     * Attach already configured Swoole HTTP server instance.
     *
     * @param Server $server
     *
     * @throws \Assert\AssertionFailedException
     */
    public function attach(Server $server): void
    {
        Assertion::null($this->server, 'Cannot attach Swoole HTTP server multiple times.');

        $this->server = $server;
    }

    /**
     * @throws \Assert\AssertionFailedException
     *
     * @return bool
     */
    public function start(): bool
    {
        Assertion::isInstanceOf($this->server, Server::class, 'Swoole HTTP Server has not been setup yet. Please use attach method.');

        return $this->running = $this->server->start();
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
}
