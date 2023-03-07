<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpKernel;

use K911\Swoole\Bridge\Symfony\HttpFoundation\RequestFactoryInterface;
use K911\Swoole\Bridge\Symfony\HttpFoundation\ResponseProcessorInjectorInterface;
use K911\Swoole\Bridge\Symfony\HttpFoundation\ResponseProcessorInterface;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use K911\Swoole\Server\Runtime\BootableInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpKernel\TerminableInterface;

final class HttpKernelRequestHandler implements RequestHandlerInterface, BootableInterface
{
    private $processorInjector;
    private $kernelPool;
    private $requestFactory;
    private $responseProcessor;

    public function __construct(
        KernelPoolInterface $kernelPool,
        RequestFactoryInterface $requestFactory,
        ResponseProcessorInjectorInterface $processorInjector,
        ResponseProcessorInterface $responseProcessor
    ) {
        $this->kernelPool = $kernelPool;
        $this->requestFactory = $requestFactory;
        $this->responseProcessor = $responseProcessor;
        $this->processorInjector = $processorInjector;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        $this->kernelPool->boot();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function handle(SwooleRequest $request, SwooleResponse $response): void
    {
        $httpFoundationRequest = $this->requestFactory->make($request);
        $this->processorInjector->injectProcessor($httpFoundationRequest, $response);
        $kernel = $this->kernelPool->get();
        $httpFoundationResponse = $kernel->handle($httpFoundationRequest);
        $this->responseProcessor->process($httpFoundationResponse, $response);

        if ($kernel instanceof TerminableInterface) {
            $kernel->terminate($httpFoundationRequest, $httpFoundationResponse);
        }

        $this->kernelPool->return($kernel);
    }
}
