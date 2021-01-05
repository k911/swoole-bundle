<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\Configurator;

use K911\Swoole\Server\Configurator\WithServerShutdownHandler;
use K911\Swoole\Server\LifecycleHandler\NoOpServerShutdownHandler;
use K911\Swoole\Tests\Unit\Server\SwooleHttpServerMock;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class WithServerShutdownHandlerTest extends TestCase
{
    /**
     * @var NoOpServerShutdownHandler
     */
    private $noOpServerShutdownHandler;

    /**
     * @var WithServerShutdownHandler
     */
    private $configurator;

    protected function setUp(): void
    {
        $this->noOpServerShutdownHandler = new NoOpServerShutdownHandler();

        $this->configurator = new WithServerShutdownHandler($this->noOpServerShutdownHandler);
    }

    public function testConfigure(): void
    {
        $swooleServerOnEventSpy = SwooleHttpServerMock::make();

        $this->configurator->configure($swooleServerOnEventSpy);

        self::assertTrue($swooleServerOnEventSpy->registeredEvent);
        self::assertSame(['shutdown', [$this->noOpServerShutdownHandler, 'handle']], $swooleServerOnEventSpy->registeredEventPair);
    }
}
