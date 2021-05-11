<?php

declare(strict_types=1);

namespace K911\Swoole\Process\Signal;

interface SignalHandlerInterface
{
    /**
     * Registers signal handler callback on one or more signal numbers.
     */
    public function register(callable $handler, Signal $signal, Signal ...$moreSignals): void;

    /**
     * Send signal immediately to provided process.
     */
    public function kill(int $processId, Signal $signal): void;
}
