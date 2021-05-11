<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use K911\Swoole\Server\WorkerHandler\WorkerExitHandlerInterface;
use Swoole\Http\Server;

final class WithWorkerExitHandler implements ConfiguratorInterface
{
    private WorkerExitHandlerInterface $handler;

    public function __construct(WorkerExitHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        $server->on('WorkerExit', [$this->handler, 'handle']);
    }
}
