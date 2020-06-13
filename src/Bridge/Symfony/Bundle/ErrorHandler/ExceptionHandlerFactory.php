<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\ErrorHandler;

use Error;
use ErrorException;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

final class ExceptionHandlerFactory
{
    /**
     * @var HttpKernelInterface
     */
    private $kernel;

    /**
     * @var ReflectionMethod
     */
    private $throwableHandler;

    /**
     * @var bool
     */
    private $isSymfony4 = false;

    public function __construct(HttpKernelInterface $kernel, ReflectionMethod $throwableHandler)
    {
        $this->kernel = $kernel;
        $this->throwableHandler = $throwableHandler;

        if ('handleException' === $throwableHandler->getName()) {
            $this->isSymfony4 = true;
        }
    }

    public function newExceptionHandler(Request $request): callable
    {
        return function (Throwable $e) use ($request) {
            if ($this->isSymfony4 && $e instanceof Error) {
                $e = new ErrorException(
                    $e->getMessage(),
                    $e->getCode(),
                    E_ERROR,
                    $e->getFile(),
                    $e->getLine(),
                    $e->getPrevious()
                );
            }

            return $this->throwableHandler->invoke($this->kernel, $e, $request, HttpKernelInterface::MASTER_REQUEST);
        };
    }
}
