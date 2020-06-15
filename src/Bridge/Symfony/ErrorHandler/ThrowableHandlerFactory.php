<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\ErrorHandler;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpKernel\HttpKernel;

final class ThrowableHandlerFactory
{
    public static function newThrowableHandler(): ReflectionMethod
    {
        $kernelReflection = new ReflectionClass(HttpKernel::class);
        $method = $kernelReflection->hasMethod('handleThrowable') ?
            $kernelReflection->getMethod('handleThrowable') : $kernelReflection->getMethod('handleException');
        $method->setAccessible(true);

        return $method;
    }
}
