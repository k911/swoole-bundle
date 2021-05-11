<?php

declare(strict_types=1);

namespace K911\Swoole\Server\WorkerHandler;

use Swoole\Server;

final class NoOpWorkerExitHandler implements WorkerExitHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(Server $worker, int $workerId): void
    {
        // noop
    }
}
