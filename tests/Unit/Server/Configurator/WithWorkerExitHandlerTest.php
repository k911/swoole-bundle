<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\Configurator;

use K911\Swoole\Server\Configurator\WithWorkerExitHandler;
use K911\Swoole\Server\WorkerHandler\NoOpWorkerExitHandler;
use K911\Swoole\Tests\Unit\Server\SwooleHttpServerMock;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class WithWorkerExitHandlerTest extends TestCase
{
    private NoOpWorkerExitHandler $noOpWorkerExitHandler;
    private WithWorkerExitHandler $configurator;

    protected function setUp(): void
    {
        $this->noOpWorkerExitHandler = new NoOpWorkerExitHandler();

        $this->configurator = new WithWorkerExitHandler($this->noOpWorkerExitHandler);
    }

    public function testConfigure(): void
    {
        $swooleServerOnEventSpy = SwooleHttpServerMock::make();

        $this->configurator->configure($swooleServerOnEventSpy);

        self::assertTrue($swooleServerOnEventSpy->registeredEvent);
        self::assertSame(['WorkerExit', [$this->noOpWorkerExitHandler, 'handle']], $swooleServerOnEventSpy->registeredEventPair);
    }
}
