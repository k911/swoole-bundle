<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use K911\Swoole\Server\LifecycleHandler\ServerStartHandlerInterface;
use Swoole\Http\Server;

final class WithServerStartHandler implements ConfiguratorInterface
{
    private $handler;

    public function __construct(ServerStartHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        $server->on('start', [$this->handler, 'handle']);
    }
}
