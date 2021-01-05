<?php

declare(strict_types=1);

namespace K911\Swoole\Process\Signal;

use Assert\Assertion;
use K911\Swoole\Process\Signal\Exception\SignalException;

final class Signal
{
    public const SIGINT = 'SIGINT';
    public const SIGKILL = 'SIGKILL';
    public const SIGTERM = 'SIGTERM';
    public const ZERO = 'ZERO';

    /**
     * OS-Portable Signals.
     *
     * @see https://en.wikipedia.org/wiki/Signal_(IPC)#Default_action
     * @see https://www.php.net/manual/en/pcntl.constants.php#108305
     * @see vendor/swoole/ide-helper/output/swoole/constants.php
     */
    private const PORTABLE_SIGNALS = [
        self::ZERO => 0,
        self::SIGINT => 2,
        self::SIGKILL => 9,
        self::SIGTERM => 15,
    ];

    private string $name;
    private int $number;

    public function __construct(string $name)
    {
        $this->name = \mb_strtoupper($name);
        $this->number = $this->resolveNumber($this->name);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function number(): int
    {
        return $this->number;
    }

    public static function kill(): self
    {
        return new self(self::SIGKILL);
    }

    public static function term(): self
    {
        return new self(self::SIGTERM);
    }

    public static function int(): self
    {
        return new self(self::SIGINT);
    }

    public static function zero(): self
    {
        return new self(self::ZERO);
    }

    private function resolveNumber(string $name): int
    {
        if (\array_key_exists($name, self::PORTABLE_SIGNALS)) {
            return self::PORTABLE_SIGNALS[$name];
        }

        if (\defined($name)) {
            $signalConstant = \constant($name);
            Assertion::integer($signalConstant, 'Signal number must be an integer. Value "%s" is not an integer.');
            Assertion::greaterOrEqualThan($signalConstant, 0, 'Provided signal number "%s" is not greater or equal than "%s".');

            return $signalConstant;
        }

        throw new SignalException(\sprintf('Signal constant "%s" is not defined. Signal number unknown', $name));
    }
}
