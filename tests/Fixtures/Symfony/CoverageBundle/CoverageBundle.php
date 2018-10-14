<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle;

use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\Coverage\CodeCoverageManager;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\EventListeners\CoverageFinishOnConsoleTerminate;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\EventListeners\CoverageStartOnConsoleCommandEventListener;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\PHP;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CoverageBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->register(PHP::class);
        $container->register(CodeCoverage::class);
        $container->autowire(CodeCoverageManager::class);

        $this->registerSingleProcessCoverageFlow($container);
    }

    private function registerSingleProcessCoverageFlow(ContainerBuilder $container): void
    {
        $container->register(CoverageStartOnConsoleCommandEventListener::class)
            ->setPublic(false)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->addTag('kernel.event_listener', ['event' => 'console.command']);

        $container->register(CoverageFinishOnConsoleTerminate::class)
            ->setPublic(false)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->addTag('kernel.event_listener', ['event' => 'console.terminate']);
    }
}
