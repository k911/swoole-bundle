<?php

declare(strict_types=1);

namespace K911\Swoole\Process;

use K911\Swoole\Component\Clock\ClockInterface;
use K911\Swoole\Process\Signal\Exception\SignalException;
use K911\Swoole\Process\Signal\Signal;
use K911\Swoole\Process\Signal\SignalHandlerInterface;

final class ProcessManager implements ProcessManagerInterface
{
    private SignalHandlerInterface $signalHandler;
    private ClockInterface $clock;

    public function __construct(SignalHandlerInterface $signalHandler, ClockInterface $clock)
    {
        $this->signalHandler = $signalHandler;
        $this->clock = $clock;
    }

    /**
     * {@inheritDoc}
     */
    public function gracefullyTerminate(int $processId, int $timeoutSeconds = 10): void
    {
        if (!$this->runningStatus($processId)) {
            throw new SignalException(\sprintf('Process with id "%d" is not running.', $processId));
        }

        $this->signalHandler->kill($processId, Signal::term());

        if (!$this->clock->timeout(fn () => $this->runningStatus($processId), $timeoutSeconds, 1000)) {
            $this->signalHandler->kill($processId, Signal::kill());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function runningStatus(int $processId): bool
    {
        try {
            $this->signalHandler->kill($processId, Signal::zero());
        } catch (SignalException $exception) {
            return false;
        }

        return true;
    }
}
