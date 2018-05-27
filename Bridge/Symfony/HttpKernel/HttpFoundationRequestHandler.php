<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpKernel;

use App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation\HttpFoundationRequestFactoryInterface;
use App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation\HttpFoundationResponseHandlerInterface;
use App\Bundle\SwooleBundle\Driver\RequestHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

final class HttpFoundationRequestHandler implements RequestHandlerInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var HttpFoundationRequestFactoryInterface
     */
    private $requestFactory;
    /**
     * @var HttpFoundationResponseHandlerInterface
     */
    private $responseHandler;

    public function __construct(KernelInterface $kernel, HttpFoundationRequestFactoryInterface $requestFactory, HttpFoundationResponseHandlerInterface $responseHandler)
    {
        $this->kernel = $kernel;
        $this->requestFactory = $requestFactory;
        $this->responseHandler = $responseHandler;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function handle(Request $request, Response $response): void
    {
        $httpFoundationRequest = $this->requestFactory->make($request);
        $httpFoundationResponse = $this->kernel->handle($httpFoundationRequest);

        $this->responseHandler->handle($httpFoundationResponse, $response);

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($httpFoundationRequest, $httpFoundationResponse);
        }
    }
}
