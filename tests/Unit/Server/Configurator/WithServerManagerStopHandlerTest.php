<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\Configurator;

use K911\Swoole\Server\Configurator\WithServerManagerStopHandler;
use K911\Swoole\Server\LifecycleHandler\NoOpServerManagerStopHandler;
use K911\Swoole\Tests\Unit\Server\SwooleHttpServerMock;
use PHPUnit\Framework\TestCase;

class WithServerManagerStopHandlerTest extends TestCase
{
    /**
     * @var NoOpServerManagerStopHandler
     */
    private $noOpServerManagerStopHandler;

    /**
     * @var WithServerManagerStopHandler
     */
    private $configurator;

    protected function setUp(): void
    {
        $this->noOpServerManagerStopHandler = new NoOpServerManagerStopHandler();

        $this->configurator = new WithServerManagerStopHandler($this->noOpServerManagerStopHandler);
    }

    public function testConfigure(): void
    {
        $swooleServerOnEventSpy = new SwooleHttpServerMock();

        $this->configurator->configure($swooleServerOnEventSpy);

        $this->assertTrue($swooleServerOnEventSpy->registeredEvent);
        $this->assertSame(['ManagerStop', [$this->noOpServerManagerStopHandler, 'handle']], $swooleServerOnEventSpy->registeredEventPair);
    }
}
