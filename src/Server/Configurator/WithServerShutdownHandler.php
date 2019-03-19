<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use K911\Swoole\Server\LifecycleHandler\ServerShutdownHandlerInterface;
use Swoole\Http\Server;

final class WithServerShutdownHandler implements ConfiguratorInterface
{
    private $handler;

    public function __construct(ServerShutdownHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        $server->on('shutdown', [$this->handler, 'handle']);
    }
}
