<?php

declare(strict_types=1);

namespace K911\Swoole\Server\LifecycleHandler;

use Swoole\Server;

interface ServerStartHandlerInterface
{
    /**
     * Handle "OnStart" event.
     *
     * @param Server $server
     */
    public function handle(Server $server): void;
}
