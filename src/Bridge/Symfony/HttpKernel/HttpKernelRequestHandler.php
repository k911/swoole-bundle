<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpKernel;

use K911\Swoole\Bridge\Symfony\HttpFoundation\RequestFactoryInterface;
use K911\Swoole\Bridge\Symfony\HttpFoundation\ResponseProcessorInterface;
use K911\Swoole\Bridge\Symfony\HttpFoundation\StreamedResponseListener;
use K911\Swoole\Bridge\Symfony\HttpFoundation\SwooleRequestResponseContextManager;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use K911\Swoole\Server\Runtime\BootableInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

final class HttpKernelRequestHandler implements RequestHandlerInterface, BootableInterface
{
    private $contextManager;
    private $kernel;
    private $requestFactory;
    private $responseProcessor;

    public function __construct(
        KernelInterface $kernel,
        RequestFactoryInterface $requestFactory,
        SwooleRequestResponseContextManager $contextManager,
        ResponseProcessorInterface $responseProcessor
    ) {
        $this->kernel = $kernel;
        $this->requestFactory = $requestFactory;
        $this->responseProcessor = $responseProcessor;
        $this->contextManager = $contextManager;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        $this->kernel->boot();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function handle(SwooleRequest $request, SwooleResponse $response): void
    {
        $httpFoundationRequest = $this->requestFactory->make($request);
        $this->contextManager->attachRequestResponseAttributes($httpFoundationRequest, $request, $response);
        $httpFoundationResponse = $this->kernel->handle($httpFoundationRequest);
        if (!$httpFoundationResponse instanceof StreamedResponse) {
            $this->responseProcessor->process($httpFoundationResponse, $response);
        }

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($httpFoundationRequest, $httpFoundationResponse);
        }
    }
}
