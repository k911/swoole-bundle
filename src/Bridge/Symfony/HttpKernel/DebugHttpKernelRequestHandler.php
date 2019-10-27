<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpKernel;

use function K911\Swoole\replace_object_property;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Stopwatch\Stopwatch;

final class DebugHttpKernelRequestHandler implements RequestHandlerInterface
{
    private $decorated;
    private $container;
    private $kernel;

    public function __construct(RequestHandlerInterface $decorated, KernelInterface $kernel, ContainerInterface $container)
    {
        $this->decorated = $decorated;
        $this->container = $container;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, Response $response): void
    {
        if ($this->kernel->isDebug()) {
            replace_object_property($this->kernel, 'startTime', \microtime(true));
        }

        $this->decorated->handle($request, $response);

        if ($this->kernel->isDebug()) {
            if ($this->container->has('debug.stopwatch')) {
                /** @var Stopwatch $stopwatch */
                $stopwatch = $this->container->get('debug.stopwatch');
                $stopwatch->reset();
            }

            if ($this->container->has('profiler')) {
                /** @var Profiler $profiler */
                $profiler = $this->container->get('profiler');
                $profiler->reset();
                $profiler->enable();
            }
        }
    }
}
