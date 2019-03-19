<?php

declare(strict_types=1);

namespace K911\Swoole\Server\WorkerHandler;

use K911\Swoole\Server\Runtime\HMR\HotModuleReloaderInterface;
use Swoole\Server;

final class HMRWorkerStartHandler implements WorkerStartHandlerInterface
{
    private $hmr;
    private $interval;
    private $decorated;

    public function __construct(HotModuleReloaderInterface $hmr, int $interval = 2000, ?WorkerStartHandlerInterface $decorated = null)
    {
        $this->hmr = $hmr;
        $this->interval = $interval;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Server $worker, int $workerId): void
    {
        if ($this->decorated instanceof WorkerStartHandlerInterface) {
            $this->decorated->handle($worker, $workerId);
        }

        if ($worker->taskworker) {
            return;
        }

        $worker->tick($this->interval, function () use ($worker): void {
            $this->hmr->tick($worker);
        });
    }
}
