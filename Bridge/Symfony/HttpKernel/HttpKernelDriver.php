<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpKernel;

use App\Bundle\SwooleBundle\Driver\DriverInterface;
use App\Bundle\SwooleBundle\Driver\RequestHandlerInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\KernelInterface;

final class HttpKernelDriver implements DriverInterface
{
    private $kernel;
    private $requestHandler;

    public function __construct(KernelInterface $kernel, RequestHandlerInterface $requestHandler)
    {
        $this->kernel = $kernel;
        $this->requestHandler = $requestHandler;
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
        $this->requestHandler->handle($request, $response);
    }
}
