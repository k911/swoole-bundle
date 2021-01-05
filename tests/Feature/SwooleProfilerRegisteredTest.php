<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Bridge\Upscale\Blackfire\WithProfiler;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;
use Upscale\Swoole\Blackfire\Profiler;

final class SwooleProfilerRegisteredTest extends ServerTestCase
{
    /**
     * Ensure that WithProfiler and Profiler are registered.
     */
    public function testWiring(): void
    {
        $kernel = static::createKernel(['environment' => 'dev']);
        $kernel->boot();

        $container = $kernel->getContainer();
        $testContainer = $container->get('test.service_container');

        self::assertTrue($testContainer->has(Profiler::class));
        self::assertTrue($testContainer->has(WithProfiler::class));
    }
}
