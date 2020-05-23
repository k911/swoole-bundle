<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\Configurator;

use K911\Swoole\Bridge\Upscale\Blackfire\WithProfiler;
use K911\Swoole\Tests\Unit\Server\SwooleHttpServerDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Upscale\Swoole\Blackfire\Profiler;

/**
 * @runTestsInSeparateProcesses
 */
class WithProfilerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @var WithProfiler
     */
    private $configurator;

    /**
     * @var ObjectProphecy|Profiler
     */
    private $configurationProphecy;

    protected function setUp(): void
    {
        $this->configurationProphecy = $this->prophesize(Profiler::class);

        /** @var Profiler $profilerMock */
        $profilerMock = $this->configurationProphecy->reveal();

        $this->configurator = new WithProfiler($profilerMock);
    }

    public function testProfiler(): void
    {
        $swooleServer = new SwooleHttpServerDummy();

        $this->configurationProphecy
            ->instrument($swooleServer)
            ->shouldBeCalled()
        ;

        $this->configurator->configure($swooleServer);
    }
}
