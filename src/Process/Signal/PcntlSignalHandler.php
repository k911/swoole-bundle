<?php

declare(strict_types=1);

namespace K911\Swoole\Process\Signal;

use Assert\Assertion;
use K911\Swoole\Process\Signal\Exception\SignalException;

final class PcntlSignalHandler implements SignalHandlerInterface
{
    private $restartSysCalls;

    /**
     * PcntlSignalHandler constructor.
     *
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(bool $restartSysCalls = false, bool $asyncSignals = true)
    {
        Assertion::extensionLoaded('pcntl', 'PHP PCNTL extension is required to use use PcntlSignalHandler. You can install it with pecl or provide "--enable-pnctl" option during PHP compilation.');
        Assertion::extensionLoaded('posix', 'PHP POSIX extension is required to use use PcntlSignalHandler. You can enable it by compiling PHP without "--disable-posix" option.');

        $this->restartSysCalls = $restartSysCalls;

        if ($asyncSignals) {
            $this->enableAsyncSignals();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function register(callable $handler, Signal $signal, Signal ...$moreSignals): void
    {
        /** @var Signal $signalObj */
        foreach ([$signal, ...$moreSignals] as $signalObj) {
            if (!\pcntl_signal($signalObj->number(), $handler, $this->restartSysCalls)) {
                $errorNumber = \posix_get_last_error();
                $errorMessage = \pcntl_strerror($errorNumber);

                throw new SignalException(\sprintf('Unable to register PCNTL signal handler on signal "%s (%d)". Error (%d): %s', $signal->name(), $signal->number(), $errorNumber, $errorMessage));
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function kill(int $processId, Signal $signal): void
    {
        if (!\posix_kill($processId, $signal->number())) {
            $errorNumber = \posix_get_last_error();
            $errorMessage = \posix_strerror($errorNumber);

            throw new SignalException(\sprintf('Killing process id "%d" with signal "%s (%d)" failed. Error (%d): %s', $processId, $signal->name(), $signal->number(), $errorNumber, $errorMessage));
        }
    }

    private function enableAsyncSignals(): void
    {
        if (!\pcntl_async_signals()) {
            \pcntl_async_signals(true);
        }
    }
}
