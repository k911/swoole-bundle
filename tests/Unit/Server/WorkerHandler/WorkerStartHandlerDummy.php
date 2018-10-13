<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\WorkerHandler;

use K911\Swoole\Server\WorkerHandler\WorkerStartHandlerInterface;
use Swoole\Server;

final class WorkerStartHandlerDummy implements WorkerStartHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(Server $worker, int $workerId): void
    {
    }
}
