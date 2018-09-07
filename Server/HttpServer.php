<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Server;

use Assert\Assertion;
use Swoole\Http\Server;

final class HttpServer
{
    /**
     * @var Server|null
     */
    private $server;

    /**
     * @var HttpServerFactory
     */
    private $serverFactory;

    public function __construct(HttpServerFactory $serverFactory)
    {
        $this->serverFactory = $serverFactory;
    }

    /**
     * @param HttpServerConfiguration $configuration
     *
     * @throws \Assert\AssertionFailedException
     */
    public function setup(HttpServerConfiguration $configuration): void
    {
        Assertion::null($this->server, 'Cannot setup swoole http server multiple times.');
        $server = $this->serverFactory->make($configuration);

        $server->set($configuration->getSwooleSettings());

        if (0 === $configuration->getPort()) {
            $configuration->changePort($server->port);
        }

        $this->server = $server;
    }

    /**
     * @param RequestHandlerInterface $driver
     *
     * @throws \Assert\AssertionFailedException
     *
     * @return bool
     */
    public function start(RequestHandlerInterface $driver): bool
    {
        Assertion::isInstanceOf($this->server, Server::class, 'Swoole HTTP Server has not been setup yet. Please use setup() method.');

        $this->server->on('request', [$driver, 'handle']);

        return $this->server->start();
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function shutdown(): void
    {
        Assertion::isInstanceOf($this->server, Server::class, 'Swoole HTTP Server has not been setup yet. Please use setup() method.');

        $this->server->shutdown();
    }
}
