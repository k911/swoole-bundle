<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\ErrorHandler;

use K911\Swoole\Bridge\Symfony\HttpFoundation\RequestFactoryInterface;
use K911\Swoole\Bridge\Symfony\HttpFoundation\ResponseProcessorInterface;
use K911\Swoole\Server\RequestHandler\ExceptionHandler\ExceptionHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Throwable;

final class SymfonyExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var HttpKernelInterface
     */
    private $kernel;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var ResponseProcessorInterface
     */
    private $responseProcessor;

    /**
     * @var ErrorResponder
     */
    private $errorResponder;

    public function __construct(
        HttpKernelInterface $kernel,
        RequestFactoryInterface $requestFactory,
        ResponseProcessorInterface $responseProcessor,
        ErrorResponder $errorResponder
    ) {
        $this->kernel = $kernel;
        $this->requestFactory = $requestFactory;
        $this->responseProcessor = $responseProcessor;
        $this->errorResponder = $errorResponder;
    }

    public function handle(Request $request, Throwable $exception, Response $response): void
    {
        $httpFoundationRequest = $this->requestFactory->make($request);
        $httpFoundationResponse = $this->errorResponder->processErroredRequest($httpFoundationRequest, $exception);
        $this->responseProcessor->process($httpFoundationResponse, $response);

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($httpFoundationRequest, $httpFoundationResponse);
        }
    }
}
