<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use K911\Swoole\Server\HttpServerConfiguration;
use K911\Swoole\Server\LifecycleHandler\ServerStartHandlerInterface;
use Swoole\Http\Server;

final class WithServerStartHandler implements ConfiguratorInterface
{
    private $handler;
    private $configuration;

    public function __construct(ServerStartHandlerInterface $handler, HttpServerConfiguration $configuration)
    {
        $this->handler = $handler;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        // see: https://github.com/swoole/swoole-src/blob/077c2dfe84d9f2c6d47a4e105f41423421dd4c43/src/server/reactor_process.cc#L181
        if ($this->configuration->isReactorRunningMode()) {
            return;
        }

        $server->on('start', [$this->handler, 'handle']);
    }
}
