<?php

declare(strict_types=1);

namespace K911\Swoole\Server\LifecycleHandler;

use Swoole\Process;
use Swoole\Server;

final class SigIntHandler implements ServerStartHandlerInterface
{
    private $decorated;
    private $signalInterrupt;

    public function __construct(?ServerStartHandlerInterface $decorated = null)
    {
        $this->decorated = $decorated;
        $this->signalInterrupt = \defined('SIGINT') ? (int) \constant('SIGINT') : 2;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Server $server): void
    {
        // 2 => SIGINT
        Process::signal($this->signalInterrupt, [$server, 'shutdown']);

        if ($this->decorated instanceof ServerStartHandlerInterface) {
            $this->decorated->handle($server);
        }
    }
}
