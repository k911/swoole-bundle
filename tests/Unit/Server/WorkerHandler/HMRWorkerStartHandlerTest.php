<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\WorkerHandler;

use K911\Swoole\Server\WorkerHandler\HMRWorkerStartHandler;
use K911\Swoole\Tests\Unit\Server\IntMother;
use K911\Swoole\Tests\Unit\Server\Runtime\HMR\HMRSpy;
use K911\Swoole\Tests\Unit\Server\SwooleServerMock;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class HMRWorkerStartHandlerTest extends TestCase
{
    /**
     * @var HMRSpy
     */
    private $hmrSpy;

    /**
     * @var HMRWorkerStartHandler
     */
    private $hmrWorkerStartHandler;

    protected function setUp(): void
    {
        $this->hmrSpy = new HMRSpy();

        $this->hmrWorkerStartHandler = new HMRWorkerStartHandler($this->hmrSpy, 2000);
    }

    public function testTaskWorkerNotRegisterTick(): void
    {
        $serverMock = SwooleServerMock::make(true);

        $this->hmrWorkerStartHandler->handle($serverMock, IntMother::random());

        self::assertFalse($serverMock->registeredTick);
    }

    public function testWorkerRegisterTick(): void
    {
        $serverMock = SwooleServerMock::make();

        $this->hmrWorkerStartHandler->handle($serverMock, IntMother::random());

        self::assertTrue($serverMock->registeredTick);
        self::assertSame(2000, $serverMock->registeredTickTuple[0]);
        $this->assertCallbackTriggersClick($serverMock->registeredTickTuple[1]);
    }

    private function assertCallbackTriggersClick(callable $callback): void
    {
        $callback();
        self::assertTrue($this->hmrSpy->tick);
    }
}
