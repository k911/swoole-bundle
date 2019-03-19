<?php

declare(strict_types=1);

namespace K911\Swoole\Server\LifecycleHandler;

use Swoole\Server;

interface ServerManagerStopHandlerInterface
{
    /**
     * Handle "OnManagerStop" event.
     *
     * Info: Handler is executed in manager process
     *
     * @param Server $server
     */
    public function handle(Server $server): void;
}
