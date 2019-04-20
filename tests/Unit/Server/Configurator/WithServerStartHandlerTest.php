<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\Configurator;

use K911\Swoole\Server\Configurator\WithServerStartHandler;
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

    protected function setUp(): void
    {
        $this->noOpServerStartHandler = new NoOpServerStartHandler();

        $this->configurator = new WithServerStartHandler($this->noOpServerStartHandler);
    }

    public function testConfigure(): void
    {
        $swooleServerOnEventSpy = SwooleHttpServerMock::make();

        $this->configurator->configure($swooleServerOnEventSpy);

        $this->assertTrue($swooleServerOnEventSpy->registeredEvent);
        $this->assertSame(['start', [$this->noOpServerStartHandler, 'handle']], $swooleServerOnEventSpy->registeredEventPair);
    }
}
