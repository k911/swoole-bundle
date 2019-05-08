<?php

declare(strict_types=1);

namespace K911\Swoole\Server\TaskHandler;

use Swoole\Server;

final class NoOpTaskHandler implements TaskHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(Server $server, int $taskId, int $fromId, $data): void
    {
        // noop
    }
}
