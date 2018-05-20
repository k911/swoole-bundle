<?php

namespace App\Bundle\SwooleBundle\Driver;

use App\Bundle\SwooleBundle\Server\ServerUtils;
use App\Kernel;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * Driver for running Symfony with Swoole as Symfony/Console command.
 */
final class ConsoleDebugDriver implements ProfilingDriverInterface
{
    private $kernel;
    private $output;
    private $profilingEnabled;
    private $trustAllProxies = false;

    /**
     * Driver constructor.
     *
     * @param \App\Kernel     $kernel
     * @param OutputInterface $output
     * @param bool            $profilingEnabled
     */
    public function __construct(Kernel $kernel, OutputInterface $output, bool $profilingEnabled = false)
    {
        $this->output = $output;
        $this->kernel = $kernel;
        $this->profilingEnabled = $profilingEnabled;
    }

    public function profilingEnabled(): bool
    {
        return $this->profilingEnabled;
    }

    /**
     * Boot Symfony Application.
     *
     * @param array $trustedHosts
     * @param array $trustedProxies
     *
     * @throws \InvalidArgumentException
     */
    public function boot(array $trustedHosts = [], array $trustedProxies = []): void
    {
        if ($this->profilingEnabled && !\gc_enabled()) {
            $this->output->writeln('<error>Garbage Collector is disabled!</error>');
        }

        if ([] !== $trustedHosts) {
            SymfonyRequest::setTrustedHosts($trustedHosts);
        }

        if ([] !== $trustedProxies) {
            if (\in_array('*', $trustedProxies, true)) {
                $this->trustAllProxies = true;
                if ($this->kernel->isDebug()) {
                    $this->output->writeln('<comment>Trusting all proxies</comment>');
                }
            } else {
                SymfonyRequest::setTrustedProxies($trustedProxies, SymfonyRequest::HEADER_X_FORWARDED_ALL);
            }
        }

        $app = $this->kernel;
        ServerUtils::bindAndCall(function () use ($app) {
            $app->boot();
        }, $app);

        $this->profile('after driver boot');
    }

    /**
     * Does some necessary preparation before each request.
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \OutOfRangeException
     */
    private function preHandle(): void
    {
        $this->profile('before handling request');

        // Reset Kernel startTime, so Symfony can correctly calculate the execution time
        $this->kernel->resetStartTime();

        $container = $this->kernel->getContainer();

        if ($container->has('doctrine.orm.entity_manager')) {
            $connection = $container->get('doctrine.orm.entity_manager')->getConnection();
            if (!$connection->ping()) {
                $connection->close();
                $connection->connect();
            }
        }
    }

    /**
     * Happens after each request.
     *
     * @throws \OutOfRangeException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    private function postHandle(): void
    {
        $container = $this->kernel->getContainer();

        //resets stopwatch, so it can correctly calculate the execution time
        if ($container->has('debug.stopwatch')) {
            $container->get('debug.stopwatch')->__construct();
        }

        if ($container->has('doctrine.orm.entity_manager')) {
            $container->get('doctrine.orm.entity_manager')->clear();
        }

        //reset all profiler stuff currently supported
        if ($container->has('profiler')) {
            $profiler = $container->get('profiler');

            // since Symfony does not reset Profiler::disable() calls after each request, we need to do it,
            // so the profiler bar is visible after the second request as well.
            $profiler->enable();

            // Doctrine
            // Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector
            if ($profiler->has('db')) {
                ServerUtils::bindAndCall(function () {
                    //$logger: \Doctrine\DBAL\Logging\DebugStack
                    foreach ($this->loggers as $logger) {
                        ServerUtils::hijackProperty($logger, 'queries', []);
                    }
                }, $profiler->get('db'), [], 'Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector');
            }

            // EventDataCollector
            if ($profiler->has('events')) {
                ServerUtils::hijackProperty($profiler->get('events'), 'data', [
                    'called_listeners' => [],
                    'not_called_listeners' => [],
                ]);
            }

            // TwigDataCollector
            if ($profiler->has('twig')) {
                ServerUtils::bindAndCall(function () {
                    ServerUtils::hijackProperty($this->profile, 'profiles', []);
                }, $profiler->get('twig'));
            }

            // Logger
            if ($container->has('logger')) {
                $logger = $container->get('logger');
                ServerUtils::bindAndCall(function () {
                    if (\method_exists($this, 'getDebugLogger') && $debugLogger = $this->getDebugLogger()) {
                        //DebugLogger
                        ServerUtils::hijackProperty($debugLogger, 'records', []);
                    }
                }, $logger);
            }
        }

        $this->profile('after sending response');
    }

    /**
     * Transform Symfony request and response to Swoole compatible response.
     *
     * @param \Swoole\Http\Request  $swooleRequest
     * @param \Swoole\Http\Response $swooleResponse
     *
     * @throws \Exception
     */
    public function handle(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse): void
    {
        $this->preHandle();

        $symfonyRequest = $this->createSymfonyRequest($swooleRequest);

        if ($this->trustAllProxies) {
            SymfonyRequest::setTrustedProxies(['127.0.0.1', $symfonyRequest->server->get('REMOTE_ADDR')], SymfonyRequest::HEADER_X_FORWARDED_ALL);
        }

        $symfonyResponse = $this->kernel->handle($symfonyRequest);

        $this->profile('during handling request');

        $this->kernel->terminate($symfonyRequest, $symfonyResponse);

        // HTTP status code for response
        $swooleResponse->status($symfonyResponse->getStatusCode());

        // Headers
        foreach ($symfonyResponse->headers->allPreserveCase() as $name => $values) {
            /** @var array $values */
            foreach ($values as $value) {
                $swooleResponse->header($name, $value);
            }
        }

        $swooleResponse->end($symfonyResponse->getContent());

        $this->postHandle();
    }

    /**
     * @param \Swoole\Http\Request $request
     *
     * @throws \LogicException
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    private function createSymfonyRequest(SwooleRequest $request): SymfonyRequest
    {
        $server = \array_change_key_case($request->server, CASE_UPPER);

        // Add formatted headers to server
        foreach ($request->header as $key => $value) {
            $server['HTTP_'.\mb_strtoupper(\str_replace('-', '_', $key))] = $value;
        }

        // Map CloudFront's forwarded proto header
        if (isset($server['HTTP_CLOUDFRONT_FORWARDED_PROTO'])) {
            $server['HTTP_X_FORWARDED_PROTO'] = $server['HTTP_CLOUDFRONT_FORWARDED_PROTO'];
        }

        $symfonyRequest = new SymfonyRequest($request->get ?? [], $request->post ?? [], [], $request->cookie ?? [], $request->files ?? [], $server, $request->rawContent());

        if (0 === \mb_strpos($symfonyRequest->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && \in_array(\mb_strtoupper($symfonyRequest->server->get('REQUEST_METHOD', 'GET')), ['PUT', 'DELETE', 'PATCH'])
        ) {
            \parse_str($symfonyRequest->getContent(), $data);
            $symfonyRequest->request = new ParameterBag($data);
        }

        return $symfonyRequest;
    }

    /**
     * {@inheritdoc}
     */
    public function profile(string $when = null): void
    {
        if ($this->profilingEnabled) {
            $messages = [];
            if (null !== $when) {
                $messages[] = \sprintf('<info>Profile occurred %s</info>', $when);
            }

            $messages[] = \sprintf('<info>Current memory usage: %s</info>', ServerUtils::formatBytes(ServerUtils::getMemoryUsage()));
            $messages[] = \sprintf('<info>Peak memory usage: %s</info>', ServerUtils::formatBytes(ServerUtils::getPeakMemoryUsage()));

            $this->output->writeln($messages);
        }
    }
}
