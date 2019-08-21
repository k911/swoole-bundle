<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\Configurator;

use K911\Swoole\Server\Configurator\WithTaskFinishedHandler;
use K911\Swoole\Server\HttpServerConfiguration;
use K911\Swoole\Server\TaskHandler\NoOpTaskFinishedHandler;
use K911\Swoole\Tests\Unit\Server\IntMother;
use K911\Swoole\Tests\Unit\Server\SwooleHttpServerMock;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @runTestsInSeparateProcesses
 */
class WithTaskFinishedHandlerTest extends TestCase
{
    /**
     * @var NoOpTaskFinishedHandler
     */
    private $noOpTaskFinishedHandler;

    /**
     * @var WithTaskFinishedHandler
     */
    private $configurator;

    /**
     * @var HttpServerConfiguration|ObjectProphecy
     */
    private $configurationProphecy;

    protected function setUp(): void
    {
        $this->noOpTaskFinishedHandler = new NoOpTaskFinishedHandler();
        $this->configurationProphecy = $this->prophesize(HttpServerConfiguration::class);

        /** @var HttpServerConfiguration $configurationMock */
        $configurationMock = $this->configurationProphecy->reveal();

        $this->configurator = new WithTaskFinishedHandler($this->noOpTaskFinishedHandler, $configurationMock);
    }

    public function testConfigure(): void
    {
        $this->configurationProphecy->getTaskWorkerCount()
            ->willReturn(IntMother::randomPositive())
            ->shouldBeCalled()
        ;

        $swooleServerOnEventSpy = SwooleHttpServerMock::make();

        $this->configurator->configure($swooleServerOnEventSpy);

        $this->assertTrue($swooleServerOnEventSpy->registeredEvent);
        $this->assertSame(['finish', [$this->noOpTaskFinishedHandler, 'handle']], $swooleServerOnEventSpy->registeredEventPair);
    }

    public function testDoNotConfigureWhenNoTaskWorkers(): void
    {
        $this->configurationProphecy->getTaskWorkerCount()
            ->willReturn(0)
            ->shouldBeCalled()
        ;

        $swooleServerOnEventSpy = SwooleHttpServerMock::make();

        $this->configurator->configure($swooleServerOnEventSpy);

        $this->assertFalse($swooleServerOnEventSpy->registeredEvent);
    }
}
