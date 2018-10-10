<?php

declare(strict_types=1);

namespace K911\Swoole\Server\WorkerHandler;

use K911\Swoole\Server\Runtime\HMR\HotModuleReloaderInterface;
use Swoole\Server;

final class HMRWorkerHandler implements WorkerStartHandlerInterface
{
    private $hmr;
    private $interval;

    public function __construct(HotModuleReloaderInterface $hmr, int $interval = 2000)
    {
        $this->hmr = $hmr;
        $this->interval = $interval;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Server $worker, int $workerId): void
    {
        if (!$worker->taskworker) {
            $worker->tick($this->interval, function () use ($worker): void {
                $this->hmr->tick($worker);
            });
        }
    }
}
