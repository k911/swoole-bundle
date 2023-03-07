<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container\Proxy\Generation\MethodGenerator;

use K911\Swoole\Bridge\Symfony\Container\Proxy\Generation\MethodGenerator\Util\InterceptorGenerator;
use Laminas\Code\Generator\Exception\InvalidArgumentException;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Reflection\MethodReflection;
use ProxyManager\Generator\MethodGenerator;

/**
 * Method with additional pre- and post- interceptor logic in the body.
 */
class InterceptedMethod extends MethodGenerator
{
    /**
     * @throws InvalidArgumentException
     */
    public static function generateMethod(
        MethodReflection $originalMethod,
        PropertyGenerator $servicePoolHolderProperty
    ): self {
        $method = static::fromReflectionWithoutBodyAndDocBlock($originalMethod);
        $forwardedParams = [];

        foreach ($originalMethod->getParameters() as $parameter) {
            $forwardedParams[] = ($parameter->isVariadic() ? '...' : '').'$'.$parameter->getName();
        }

        $method->setBody(InterceptorGenerator::createInterceptedMethodBody(
            $originalMethod->getName().'('.\implode(', ', $forwardedParams).')',
            $servicePoolHolderProperty,
            $originalMethod
        ));

        return $method;
    }
}
