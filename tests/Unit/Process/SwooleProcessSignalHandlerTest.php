<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Process;

use K911\Swoole\Process\Signal\Exception\SignalException;
use K911\Swoole\Process\Signal\Signal;
use K911\Swoole\Process\Signal\SwooleProcessSignalHandler;
use PHPUnit\Framework\TestCase;
use Swoole\Event;
use Swoole\Runtime;

final class SwooleProcessSignalHandlerTest extends TestCase
{
    private SwooleProcessSignalHandler $swooleProcesSignalHandler;

    protected function setUp(): void
    {
        $this->swooleProcesSignalHandler = new SwooleProcessSignalHandler();
    }

    public function testSignalRegisteredAndExecuted(): void
    {
        Runtime::enableCoroutine(true, \SWOOLE_HOOK_ALL);
        \go(function (): void {
            $signal = new Signal('SIGUSR2');
            $signaled = false;
            $this->swooleProcesSignalHandler->register(function () use (&$signaled): void {
                $signaled = true;
            }, $signal);

            self::assertFalse($signaled);
            $this->swooleProcesSignalHandler->kill(\getmypid(), $signal);
            \sleep(1);
            self::assertTrue($signaled);
        });
        Event::wait();
    }

    public function testKillNotExistingProcessExpectSignalException(): void
    {
        $this->expectException(SignalException::class);
        $this->expectExceptionMessage('Unable to kill process having id "9999" using signal "ZERO (0)"');
        $this->swooleProcesSignalHandler->kill(9999, new Signal('ZERO'));
    }
}
