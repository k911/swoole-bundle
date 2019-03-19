<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\Configurator;

use K911\Swoole\Server\Configurator\WithWorkerStartHandler;
use K911\Swoole\Server\WorkerHandler\NoOpWorkerStartHandler;
use K911\Swoole\Tests\Unit\Server\SwooleHttpServerMock;
use PHPUnit\Framework\TestCase;

class WithWorkerStartHandlerTest extends TestCase
{
    /**
     * @var NoOpWorkerStartHandler
     */
    private $noOpWorkerStartHandler;

    /**
     * @var WithWorkerStartHandler
     */
    private $configurator;

    protected function setUp(): void
    {
        $this->noOpWorkerStartHandler = new NoOpWorkerStartHandler();

        $this->configurator = new WithWorkerStartHandler($this->noOpWorkerStartHandler);
    }

    public function testConfigure(): void
    {
        $swooleServerOnEventSpy = new SwooleHttpServerMock();

        $this->configurator->configure($swooleServerOnEventSpy);

        $this->assertTrue($swooleServerOnEventSpy->registeredEvent);
        $this->assertSame(['WorkerStart', [$this->noOpWorkerStartHandler, 'handle']], $swooleServerOnEventSpy->registeredEventPair);
    }
}
