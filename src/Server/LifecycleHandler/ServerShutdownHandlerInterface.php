<?php

declare(strict_types=1);

namespace K911\Swoole\Server\LifecycleHandler;

use Swoole\Server;

interface ServerShutdownHandlerInterface
{
    /**
     * Handle "OnShutdown" event.
     */
    public function handle(Server $server): void;
}
