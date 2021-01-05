<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use Swoole\Http\Server;
use Swoole\Process;

final class WithProcess implements ConfiguratorInterface
{
    private Process $process;

    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    public function configure(Server $server): void
    {
        $server->addProcess($this->process);
    }
}
