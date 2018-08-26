<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Server;

use RuntimeException;
use Swoole\Http\Server;
use Symfony\Component\Console\Style\SymfonyStyle;

final class HttpServer
{
    private $server;

    /**
     * @var SymfonyStyle|null
     */
    private $symfonyStyle;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function setSymfonyStyle(SymfonyStyle $symfonyStyle): void
    {
        $this->symfonyStyle = $symfonyStyle;
    }

    public function start(HttpServerDriverInterface $driver, HttpServerConfiguration $configuration): void
    {
        $this->server->port = $configuration->getPort();
        $this->server->host = $configuration->getHost();
        $this->server->on('request', [$driver, 'handle']);
        $this->server->set($configuration->getSwooleSettings());

        $this->havingSymfonyStyle(function (SymfonyStyle $io): void {
            if (!$this->server->start()) {
                $io->error('Failure during starting Swoole HTTP Server.');
            } else {
                $io->success('Swoole HTTP Server has been successfully shutdown.');
            }
        }, function (): void {
            if (!$this->server->start()) {
                throw new RuntimeException('Failure during starting Swoole HTTP Server.');
            }
        });
    }

    public function shutdown(): void
    {
        $this->server->shutdown();
    }

    /**
     * @param callable $having    executes function if symfony style is available
     * @param callable $notHaving executes function if symfony style is unavailable
     *
     * @return mixed
     */
    private function havingSymfonyStyle(callable $having, callable $notHaving)
    {
        if ($this->symfonyStyle instanceof SymfonyStyle) {
            return $having($this->symfonyStyle);
        }

        return $notHaving();
    }
}
