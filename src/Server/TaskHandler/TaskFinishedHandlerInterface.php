<?php

declare(strict_types=1);

namespace K911\Swoole\Server\TaskHandler;

use Swoole\Server;

/**
 * Task Finished Handler is called only when Task Handler returns any result or Swoole\Server->finish() is called.
 *
 * @see https://www.swoole.co.uk/docs/modules/swoole-server/callback-functions#onfinish
 */
interface TaskFinishedHandlerInterface
{
    /**
     * @param mixed $data
     */
    public function handle(Server $server, int $taskId, $data): void;
}
