<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle;

use K911\Swoole\Server\LifecycleHandler\ServerManagerStartHandlerInterface;
use K911\Swoole\Server\LifecycleHandler\ServerManagerStopHandlerInterface;
use K911\Swoole\Server\LifecycleHandler\ServerShutdownHandlerInterface;
use K911\Swoole\Server\LifecycleHandler\ServerStartHandlerInterface;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use K911\Swoole\Server\WorkerHandler\WorkerStartHandlerInterface;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\Coverage\CodeCoverageManager;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\EventListeners\CoverageFinishOnConsoleTerminate;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\EventListeners\CoverageStartOnConsoleCommandEventListener;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\RequestHandler\CodeCoverageRequestHandler;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\ServerLifecycle\CoverageFinishOnServerShutdown;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\ServerLifecycle\CoverageStartOnServerManagerStart;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\ServerLifecycle\CoverageStartOnServerManagerStop;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\ServerLifecycle\CoverageStartOnServerStart;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\ServerLifecycle\CoverageStartOnServerWorkerStart;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\PHP;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CoverageBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->register(PHP::class);
        $container->register(CodeCoverage::class);
        $container->autowire(CodeCoverageManager::class);

        $this->registerSingleProcessCoverageFlow($container);
        $this->registerServerCoverageFlow($container);
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

    private function registerServerCoverageFlow(ContainerBuilder $container): void
    {
        $container->autowire(CodeCoverageRequestHandler::class)
            ->setPublic(false)
            ->setAutoconfigured(true)
            ->setArgument('$decorated', new Reference(CodeCoverageRequestHandler::class.'.inner'))
            ->setDecoratedService(RequestHandlerInterface::class, null, -9999);

        $container->autowire(CoverageStartOnServerStart::class)
            ->setPublic(false)
            ->setAutoconfigured(true)
            ->setArgument('$decorated', new Reference(CoverageStartOnServerStart::class.'.inner'))
            ->setDecoratedService(ServerStartHandlerInterface::class);

        $container->autowire(CoverageFinishOnServerShutdown::class)
            ->setPublic(false)
            ->setAutoconfigured(true)
            ->setArgument('$decorated', new Reference(CoverageFinishOnServerShutdown::class.'.inner'))
            ->setDecoratedService(ServerShutdownHandlerInterface::class);

        $container->autowire(CoverageStartOnServerWorkerStart::class)
            ->setPublic(false)
            ->setAutoconfigured(true)
            ->setArgument('$decorated', new Reference(CoverageStartOnServerWorkerStart::class.'.inner'))
            ->setDecoratedService(WorkerStartHandlerInterface::class);

        $container->autowire(CoverageStartOnServerManagerStart::class)
            ->setPublic(false)
            ->setAutoconfigured(true)
            ->setArgument('$decorated', new Reference(CoverageStartOnServerManagerStart::class.'.inner'))
            ->setDecoratedService(ServerManagerStartHandlerInterface::class);

        $container->autowire(CoverageStartOnServerManagerStop::class)
            ->setPublic(false)
            ->setAutoconfigured(true)
            ->setArgument('$decorated', new Reference(CoverageStartOnServerManagerStop::class.'.inner'))
            ->setDecoratedService(ServerManagerStopHandlerInterface::class);
    }
}
