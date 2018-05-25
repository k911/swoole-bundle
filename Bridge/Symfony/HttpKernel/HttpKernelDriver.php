<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpKernel;

use App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation\HttpFoundationRequestFactoryInterface;
use App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation\HttpFoundationResponseHandlerInterface;
use App\Bundle\SwooleBundle\Driver\DriverInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\Kernel;

final class HttpKernelDriver implements DriverInterface
{
    private $kernel;
    private $requestFactory;
    private $responseHandler;

    public function __construct(Kernel $kernel, HttpFoundationRequestFactoryInterface $requestFactory, HttpFoundationResponseHandlerInterface $responseHandler)
    {
        $this->kernel = $kernel;
        $this->requestFactory = $requestFactory;
        $this->responseHandler = $responseHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $configuration = []): void
    {
        if (\array_key_exists('trustedHosts', $configuration)) {
            SymfonyRequest::setTrustedHosts($configuration['trustedHosts']);
        }
        if (\array_key_exists('trustedProxies', $configuration)) {
            SymfonyRequest::setTrustedProxies($configuration['trustedProxies'], $configuration['trustedHeaderSet'] ?? SymfonyRequest::HEADER_X_FORWARDED_ALL);
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

        $this->responseHandler->handle($httpFoundationResponse, $response);

        $this->kernel->terminate($httpFoundationRequest, $httpFoundationResponse);
    }
}
