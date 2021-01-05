<?php

declare(strict_types=1);

namespace K911\Swoole\Process;

use Swoole\Process;

interface ProcessInterface
{
    public function run(Process $self): void;
}
