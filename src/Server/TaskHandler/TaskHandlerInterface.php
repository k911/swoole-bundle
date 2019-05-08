<?php

declare(strict_types=1);

namespace K911\Swoole\Server\TaskHandler;

use Swoole\Server;

interface TaskHandlerInterface
{
    public function handle(Server $server, int $taskId, int $fromId, $data): void;
}
