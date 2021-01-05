<?php

declare(strict_types=1);

namespace K911\Swoole\Component\Clock;

use Assert\Assertion;
use Swoole\Runtime;

/**
 * Class CoroutineFriendlyClock.
 *
 * Requires setting SWOOLE_HOOK_SLEEP on Swoole\Runtime
 * Example swoole bundle config:
 *      swoole:
 *          http_server:
 *              coroutine:
 *                  enabled: true
 *                  hooks:
 *                   - "all" # or "sleep"
 *
 * Example PHP Configuration
 *      Runtime::enableCoroutine(true, SWOOLE_HOOK_ALL);
 */
final class CoroutineFriendlyClock implements ClockInterface
{
    private bool $coroutineSleepHookEnabled;

    public function __construct(bool $coroutineSleepHookEnabled = false)
    {
        $this->coroutineSleepHookEnabled = $coroutineSleepHookEnabled;
    }

    public function timeout(callable $condition, float $timeoutSeconds = 10, int $stepMicroseconds = 1000): bool
    {
        $now = $this->currentTime();
        $start = $now;
        $max = $start + $timeoutSeconds;

        do {
            if ($condition()) {
                return true;
            }

            $now = $this->currentTime();
            $this->microSleep($stepMicroseconds);
        } while ($now < $max);

        return false;
    }

    public function currentTime(): float
    {
        return \microtime(true);
    }

    public function microSleep(int $microseconds): void
    {
        $this->sleepCoroutineHookCheck();
        \usleep($microseconds);
    }

    private function sleepCoroutineHookCheck(): void
    {
        if ($this->coroutineSleepHookEnabled) {
            return;
        }

        $this->coroutineSleepHookEnabled = (Runtime::getHookFlags() & \SWOOLE_HOOK_SLEEP) === \SWOOLE_HOOK_SLEEP;
        Assertion::true($this->coroutineSleepHookEnabled, 'Swoole Coroutine hook "SWOOLE_HOOK_SLEEP" must be enabled');
    }
}
