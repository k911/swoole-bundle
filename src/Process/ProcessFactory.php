<?php

declare(strict_types=1);

namespace K911\Swoole\Process;

use Swoole\Process as SwooleProcess;

final class ProcessFactory
{
    public function make(ProcessInterface $process, bool $redirectStdinAndStout = false, int $pipeType = 1, bool $enableCoroutine = true): SwooleProcess
    {
        return new SwooleProcess([$process, 'run'], $redirectStdinAndStout, $pipeType, $enableCoroutine);
    }
}
