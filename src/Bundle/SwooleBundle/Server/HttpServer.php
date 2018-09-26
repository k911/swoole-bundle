<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Server;

use Assert\Assertion;
use Swoole\Http\Server;

final class HttpServer
{
    private const SWOOLE_HTTP_SERVER_HAS_NOT_BEEN_INITIALIZED_MESSAGE = 'Swoole HTTP Server has not been setup yet. Please use setup or attach method.';

    /**
     * @var Server|null
     */
    private $server;

    private $running;
    private $configuration;

    public function __construct(HttpServerConfiguration $configuration, bool $running = false)
    {
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
        Assertion::isInstanceOf($this->server, Server::class, self::SWOOLE_HTTP_SERVER_HAS_NOT_BEEN_INITIALIZED_MESSAGE);

        return $this->running = $this->server->start();
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function shutdown(): void
    {
        Assertion::isInstanceOf($this->server, Server::class, self::SWOOLE_HTTP_SERVER_HAS_NOT_BEEN_INITIALIZED_MESSAGE);

        $this->server->shutdown();
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running || $this->configuration->existsPidFile();
    }
}
