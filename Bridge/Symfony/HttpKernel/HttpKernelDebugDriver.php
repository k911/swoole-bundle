<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpKernel;

use App\Bundle\SwooleBundle\Driver\DriverInterface;
use App\Bundle\SwooleBundle\Server\ServerUtils;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class HttpKernelDebugDriver implements DriverInterface
{
    private $decorated;
    private $container;

    public function __construct(DriverInterface $decorated, ContainerInterface $container)
    {
        $this->decorated = $decorated;
        $this->container = $container;
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
        ServerUtils::hijackProperty($this->container->get('kernel'), 'startTime', \microtime(true));

        $this->decorated->handle($request, $response);

        if ($this->container->has('debug.stopwatch')) {
            $this->container->get('debug.stopwatch')->reset();
        }

        if ($this->container->has('profiler')) {
            $profiler = $this->container->get('profiler');
            $profiler->reset();
            $profiler->enable();

//            // Doctrine
//            // Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector
//            if ($profiler->has('db')) {
//                ServerUtils::bindAndCall(function () {
//                    //$logger: \Doctrine\DBAL\Logging\DebugStack
//                    foreach ($this->loggers as $logger) {
//                        ServerUtils::hijackProperty($logger, 'queries', []);
//                    }
//                }, $profiler->get('db'), [], 'Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector');
//            }
//
//            // EventDataCollector
//            if ($profiler->has('events')) {
//                ServerUtils::hijackProperty($profiler->get('events'), 'data', [
//                    'called_listeners' => [],
//                    'not_called_listeners' => [],
//                ]);
//            }
//
//            // TwigDataCollector
//            if ($profiler->has('twig')) {
//                ServerUtils::bindAndCall(function () {
//                    ServerUtils::hijackProperty($this->profile, 'profiles', []);
//                }, $profiler->get('twig'));
//            }
//
//            // Logger
//            if ($this->container->has('logger')) {
//                $logger = $this->container->get('logger');
//                ServerUtils::bindAndCall(function () {
//                    if (\method_exists($this, 'getDebugLogger') && $debugLogger = $this->getDebugLogger()) {
//                        //DebugLogger
//                        ServerUtils::hijackProperty($debugLogger, 'records', []);
//                    }
//                }, $logger);
//            }
        }
    }
}
