<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpKernel;

use App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation\RequestFactoryInterface;
use App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation\ResponseProcessorInterface;
use App\Bundle\SwooleBundle\Server\HttpServerDriverInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

final class HttpKernelHttpServerDriver implements HttpServerDriverInterface
{
    private $kernel;
    private $requestFactory;
    private $responseProcessor;

    public function __construct(KernelInterface $kernel, RequestFactoryInterface $requestFactory, ResponseProcessorInterface $responseProcessor)
    {
        $this->kernel = $kernel;
        $this->requestFactory = $requestFactory;
        $this->responseProcessor = $responseProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        if (\array_key_exists('trustedHosts', $runtimeConfiguration)) {
            SymfonyRequest::setTrustedHosts($runtimeConfiguration['trustedHosts']);
        }
        if (\array_key_exists('trustedProxies', $runtimeConfiguration)) {
            SymfonyRequest::setTrustedProxies($runtimeConfiguration['trustedProxies'], $runtimeConfiguration['trustedHeaderSet'] ?? SymfonyRequest::HEADER_X_FORWARDED_ALL);
        }

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
        $httpFoundationResponse = $this->kernel->handle($httpFoundationRequest);
        $this->responseProcessor->process($httpFoundationResponse, $response);

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($httpFoundationRequest, $httpFoundationResponse);
        }
    }
}
