<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use K911\Swoole\Server\WorkerHandler\WorkerStartHandlerInterface;
use Swoole\Http\Server;

final class WithWorkerStartHandler implements ConfiguratorInterface
{
    private $decorated;
    private $handler;

    public function __construct(ConfiguratorInterface $decorated, WorkerStartHandlerInterface $handler)
    {
        $this->handler = $handler;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        $this->decorated->configure($server);

        $server->on('WorkerStart', [$this->handler, 'handle']);
    }
}
