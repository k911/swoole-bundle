<?php

declare(strict_types=1);

namespace K911\Swoole\Server\WorkerHandler;

use Swoole\Server;

interface WorkerExitHandlerInterface
{
    /**
     * Handle onWorkerExit event.
     * Info: Function will be executed in worker process.
     *
     * @see https://wiki.swoole.com/#/server/events?id=onworkerexit
     * @see https://wiki.swoole.com/#/question/use?id=%e8%bf%9b%e7%a8%8b%e9%80%80%e5%87%ba%e4%ba%8b%e4%bb%b6
     */
    public function handle(Server $worker, int $workerId): void;
}
