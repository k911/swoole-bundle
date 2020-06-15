<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\ErrorHandler;

use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ErrorResponder
{
    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    /**
     * @var ExceptionHandlerFactory
     */
    private $handlerFactory;

    public function __construct(ErrorHandler $errorHandler, ExceptionHandlerFactory $handlerFactory)
    {
        $this->errorHandler = $errorHandler;
        $this->handlerFactory = $handlerFactory;
    }

    public function processErroredRequest(Request $request, Throwable $throwable): Response
    {
        $exceptionHandler = $this->handlerFactory->newExceptionHandler($request);
        $this->errorHandler->setExceptionHandler($exceptionHandler);

        return $this->errorHandler->handleException($throwable);
    }
}
