<?php

declare(strict_types=1);

namespace K911\Swoole\Server\WorkerHandler;

use Swoole\Server;
use Swoole\Timer;

final class ClearAllTimersWorkerExitHandler implements WorkerExitHandlerInterface
{
    private ?WorkerExitHandlerInterface $decorated;

    public function __construct(?WorkerExitHandlerInterface $decorated = null)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(Server $worker, int $workerId): void
    {
        if ($this->decorated instanceof WorkerExitHandlerInterface) {
            $this->decorated->handle($worker, $workerId);
        }

        Timer::clearAll();
    }
}
