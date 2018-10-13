<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\Configurator;

use K911\Swoole\Server\Configurator\WithWorkerStartHandler;
use K911\Swoole\Tests\Unit\Server\SwooleHttpServerMock;
use K911\Swoole\Tests\Unit\Server\WorkerHandler\WorkerStartHandlerDummy;
use PHPUnit\Framework\TestCase;

class WithWorkerStartHandlerTest extends TestCase
{
    /**
     * @var WorkerStartHandlerDummy
     */
    private $workerStartHandlerDummy;

    /**
     * @var WithWorkerStartHandler
     */
    private $configurator;

    protected function setUp(): void
    {
        $this->workerStartHandlerDummy = new WorkerStartHandlerDummy();

        $this->configurator = new WithWorkerStartHandler($this->workerStartHandlerDummy);
    }

    public function testConfigure(): void
    {
        $swooleServerOnEventSpy = new SwooleHttpServerMock();

        $this->configurator->configure($swooleServerOnEventSpy);

        $this->assertTrue($swooleServerOnEventSpy->registeredEvent);
        $this->assertSame(['WorkerStart', [$this->workerStartHandlerDummy, 'handle']], $swooleServerOnEventSpy->registeredEventPair);
    }
}
