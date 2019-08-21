<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\Configurator;

use K911\Swoole\Server\Configurator\WithServerStartHandler;
use K911\Swoole\Server\HttpServerConfiguration;
use K911\Swoole\Server\LifecycleHandler\NoOpServerStartHandler;
use K911\Swoole\Tests\Unit\Server\SwooleHttpServerMock;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class WithServerStartHandlerTest extends TestCase
{
    /**
     * @var NoOpServerStartHandler
     */
    private $noOpServerStartHandler;

    /**
     * @var WithServerStartHandler
     */
    private $configurator;

    /**
     * @var HttpServerConfiguration|\Prophecy\Prophecy\ObjectProphecy
     */
    private $httpServerConfigurationMock;

    protected function setUp(): void
    {
        $this->httpServerConfigurationMock = $this->prophesize(HttpServerConfiguration::class);
        $this->noOpServerStartHandler = new NoOpServerStartHandler();

        $this->configurator = new WithServerStartHandler($this->noOpServerStartHandler, $this->httpServerConfigurationMock->reveal());
    }

    public function testConfigureNoReactorMode(): void
    {
        $this->httpServerConfigurationMock->isReactorRunningMode()
            ->willReturn(false)
            ->shouldBeCalled()
        ;

        $swooleServerOnEventSpy = SwooleHttpServerMock::make();

        $this->configurator->configure($swooleServerOnEventSpy);

        $this->assertTrue($swooleServerOnEventSpy->registeredEvent);
        $this->assertSame(['start', [$this->noOpServerStartHandler, 'handle']], $swooleServerOnEventSpy->registeredEventPair);
    }

    public function testConfigureReactorMode(): void
    {
        $this->httpServerConfigurationMock->isReactorRunningMode()
            ->willReturn(true)
            ->shouldBeCalled()
        ;

        $swooleServerOnEventSpy = SwooleHttpServerMock::make();

        $this->configurator->configure($swooleServerOnEventSpy);

        $this->assertFalse($swooleServerOnEventSpy->registeredEvent);
    }
}
