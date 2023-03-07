<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container\Proxy\Generation\MethodGenerator\Util;

use Laminas\Code\Generator\PropertyGenerator;
use ProxyManager\Generator\Util\ProxiedMethodReturnExpression;
use ReflectionMethod;

/**
 * Utility to service pool method interceptor.
 */
class InterceptorGenerator
{
    private const TEMPLATE = '
        $wrapped = $this->{{$servicePoolHolderName}}->get();
        $returnValue = $wrapped->{{$forwardedMethodCall}};

        {{$returnExpression}}
        ';

    /**
     * @param string $forwardedMethodCall the call to the proxied method
     */
    public static function createInterceptedMethodBody(
        string $forwardedMethodCall,
        PropertyGenerator $servicePoolHolder,
        ?ReflectionMethod $originalMethod
    ): string {
        $servicePoolHolderName = $servicePoolHolder->getName();
        $replacements = [
            '{{$servicePoolHolderName}}' => $servicePoolHolderName,
            '{{$forwardedMethodCall}}' => $forwardedMethodCall,
            '{{$returnExpression}}' => ProxiedMethodReturnExpression::generate('$returnValue', $originalMethod),
        ];

        return \str_replace(\array_keys($replacements), $replacements, self::TEMPLATE);
    }
}
