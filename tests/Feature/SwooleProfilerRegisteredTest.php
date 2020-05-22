<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Bridge\Upscale\Blackfire\WithProfiler;
use K911\Swoole\Server\Configurator\CallableChainConfigurator;
use K911\Swoole\Server\Configurator\WithRequestHandler;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;
use ReflectionClass;
use Upscale\Swoole\Blackfire\Profiler;

final class SwooleProfilerRegisteredTest extends ServerTestCase
{
    /**
     * Ensure that WithProfiler is added after WithRequestHandler.
     */
    public function testWiring(): void
    {
        $kernel = static::createKernel(['environment' => 'dev']);
        $kernel->boot();

        $container = $kernel->getContainer();
        $testContainer = $container->get('test.service_container');

        $inspectionFn = function (CallableChainConfigurator $c): void {
            $rClass = new ReflectionClass($c);
            $rProp = $rClass->getProperty('configurators');
            $rProp->setAccessible(true);
            $configurators = $rProp->getValue($c);
            $configuratorClasses = [];
            foreach ($configurators as [$configurator, $fn]) {
                if ('object' === \gettype($configurator)
                    && ($configurator instanceof WithProfiler || $configurator instanceof WithRequestHandler)
                ) {
                    $configuratorClasses[] = \get_class($configurator);
                }
            }
            $this->assertEquals([
                WithRequestHandler::class,
                WithProfiler::class,
            ], $configuratorClasses);
        };

        $this->assertInstanceOf(Profiler::class, $testContainer->get(Profiler::class));

        $inspectionFn($testContainer->get('swoole_bundle.server.http_server.configurator.for_server_run_command'));
        $inspectionFn($testContainer->get('swoole_bundle.server.http_server.configurator.for_server_start_command'));
    }
}
