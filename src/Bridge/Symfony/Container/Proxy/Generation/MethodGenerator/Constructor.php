<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container\Proxy\Generation\MethodGenerator;

use K911\Swoole\Bridge\Symfony\Container\ServicePool;
use Laminas\Code\Generator\Exception\InvalidArgumentException;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use ProxyManager\Generator\MethodGenerator;

/**
 * The `__construct` implementation for proxies with cooperative multitasking.
 */
class Constructor extends MethodGenerator
{
    /**
     * @throws InvalidArgumentException
     */
    public static function generateMethod(PropertyGenerator $servicePoolHolder): self
    {
        $constructor = new self('__construct');
        $constructor->setParameter(new ParameterGenerator('servicePool', ServicePool::class));
        $constructor->setBody(
            '    $this->'.$servicePoolHolder->getName().' = $servicePool;'."\n"
        );

        return $constructor;
    }
}
