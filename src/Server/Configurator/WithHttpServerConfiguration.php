<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use K911\Swoole\Server\HttpServerConfiguration;
use Swoole\Http\Server;

final class WithHttpServerConfiguration implements ConfiguratorInterface
{
    private $configuration;

    public function __construct(HttpServerConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        $server->set($this->configuration->getSwooleSettings());

        $defaultSocket = $this->configuration->getDefaultSocket();
        if (0 === $defaultSocket->port()) {
            $this->configuration->changeDefaultSocket($defaultSocket->withPort($server->port));
        }
    }
}
