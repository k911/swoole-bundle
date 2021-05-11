<?php

declare(strict_types=1);

namespace K911\Swoole\Component\Clock;

interface ClockInterface
{
    /**
     * Blocks until the condition() function returns true or timeout period elapses.
     * Returns true when condition() was able to return true value before timeout period elapsed, false otherwise.
     */
    public function timeout(callable $condition, float $timeoutSeconds = 10, int $stepMicroseconds = 1000): bool;

    /**
     * The current time in seconds since the Unix epoch accurate to the nearest microsecond.
     */
    public function currentTime(): float;

    /**
     * Blocks for duration of specified amount of microseconds.
     */
    public function microSleep(int $microseconds): void;
}
