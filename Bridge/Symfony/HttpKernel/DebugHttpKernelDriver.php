<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpKernel;

use App\Bundle\SwooleBundle\Driver\DriverInterface;
use App\Bundle\SwooleBundle\Server\ServerUtils;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class DebugHttpKernelDriver implements DriverInterface
{
    private $decorated;
    private $container;
    private $kernel;

    public function __construct(DriverInterface $decorated, KernelInterface $kernel, ContainerInterface $container)
    {
        $this->decorated = $decorated;
        $this->container = $container;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $configuration = []): void
    {
        $this->decorated->boot($configuration);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, Response $response): void
    {
        if ($this->kernel->isDebug()) {
            ServerUtils::hijackProperty($this->kernel, 'startTime', \microtime(true));
        }

        $this->decorated->handle($request, $response);

        if ($this->kernel->isDebug()) {
            if ($this->container->has('debug.stopwatch')) {
                $this->container->get('debug.stopwatch')->reset();
            }

            if ($this->container->has('profiler')) {
                $profiler = $this->container->get('profiler');
                $profiler->reset();
                $profiler->enable();
            }
        }
    }
}
