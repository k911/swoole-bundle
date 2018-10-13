<?php

declare(strict_types=1);

namespace K911\Swoole\Server\LifecycleHandler;

use Swoole\Process;
use Swoole\Server;

final class SigIntHandler implements ServerStartHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(Server $server): void
    {
        // 2 => SIGINT
        Process::signal(2, [$server, 'shutdown']);
    }
}
