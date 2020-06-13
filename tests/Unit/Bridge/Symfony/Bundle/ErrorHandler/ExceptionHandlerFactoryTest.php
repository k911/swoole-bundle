<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Bridge\Symfony\Bundle\ErrorHandler;

use Error;
use ErrorException;
use K911\Swoole\Bridge\Symfony\Bundle\ErrorHandler\ExceptionHandlerFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernel;

final class ExceptionHandlerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreatedExceptionHandler(): void
    {
        $error = new Error('Error');
        $kernelMock = $this->prophesize(HttpKernel::class)->reveal();
        $requestMock = $this->prophesize(Request::class)->reveal();
        $throwableHandlerProphecy = $this->prophesize(ReflectionMethod::class);
        $throwableHandlerProphecy->getName()->willReturn('handleThrowable');
        $throwableHandlerProphecy->invoke()->withArguments([
            $kernelMock,
            $error,
            $requestMock,
            HttpKernel::MASTER_REQUEST,
        ])->shouldBeCalled();
        $throwableHandlerMock = $throwableHandlerProphecy->reveal();

        $factory = new ExceptionHandlerFactory($kernelMock, $throwableHandlerMock);
        $handler = $factory->newExceptionHandler($requestMock);
        $handler($error);
    }

    public function testCreatedExceptionHandlerWithConversionToErrorException(): void
    {
        $error = new Error('Error');
        $kernelMock = $this->prophesize(HttpKernel::class)->reveal();
        $requestMock = $this->prophesize(Request::class)->reveal();
        $throwableHandlerProphecy = $this->prophesize(ReflectionMethod::class);
        $throwableHandlerProphecy->getName()->willReturn('handleException');
        $throwableHandlerProphecy->invoke()->withArguments([
            $kernelMock,
            Argument::type(ErrorException::class),
            $requestMock,
            HttpKernel::MASTER_REQUEST,
        ])->shouldBeCalled();
        $throwableHandlerMock = $throwableHandlerProphecy->reveal();

        $factory = new ExceptionHandlerFactory($kernelMock, $throwableHandlerMock);
        $handler = $factory->newExceptionHandler($requestMock);
        $handler($error);
    }
}
