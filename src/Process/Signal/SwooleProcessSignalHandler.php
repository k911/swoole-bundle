<?php

declare(strict_types=1);

namespace K911\Swoole\Process\Signal;

use K911\Swoole\Process\Signal\Exception\SignalException;
use Swoole\Process;

final class SwooleProcessSignalHandler implements SignalHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(callable $handler, Signal $signal, Signal ...$moreSignals): void
    {
        try {
            /** @var Signal $signalObj */
            foreach ([$signal, ...$moreSignals] as $signalObj) {
                Process::signal($signalObj->number(), $handler);
            }
        } catch (\Throwable $exception) {
            throw SignalException::fromThrowable($exception);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function kill(int $processId, Signal $signal): void
    {
        try {
            if (!Process::kill($processId, $signal->number())) {
                throw SignalException::fromKillCommand($processId, $signal);
            }
        } catch (\Throwable $exception) {
            throw SignalException::fromThrowable($exception);
        }
    }
}
