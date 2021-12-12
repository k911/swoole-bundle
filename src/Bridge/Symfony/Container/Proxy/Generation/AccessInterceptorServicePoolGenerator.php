<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container\Proxy\Generation;

use InvalidArgumentException;
use K911\Swoole\Bridge\Symfony\Container\Proxy\Generation\MethodGenerator\Constructor;
use K911\Swoole\Bridge\Symfony\Container\Proxy\Generation\PropertyGenerator\ServicePoolHolderProperty;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use ProxyManager\Exception\InvalidProxiedClassException;
use ProxyManager\Generator\Util\ClassGeneratorUtils;
use ProxyManager\ProxyGenerator\Assertion\CanProxyAssertion;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;
use ProxyManager\ProxyGenerator\Util\ProxiedMethodsFilter;
use ReflectionClass;

/**
 * Generator for proxies with service pool.
 */
class AccessInterceptorServicePoolGenerator implements ProxyGeneratorInterface
{
    private $methodInterceptorBuilder;

    public function __construct(MethodInterceptorBuilder $methodInterceptorBuilder)
    {
        $this->methodInterceptorBuilder = $methodInterceptorBuilder;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws InvalidProxiedClassException
     */
    public function generate(ReflectionClass $originalClass, ClassGenerator $classGenerator): void
    {
        CanProxyAssertion::assertClassCanBeProxied($originalClass);

        $interfaces = [];

        if ($originalClass->isInterface()) {
            $interfaces[] = $originalClass->getName();
        }

        if (!$originalClass->isInterface()) {
            $classGenerator->setExtendedClass($originalClass->getName());
        }

        $classGenerator->setImplementedInterfaces($interfaces);
        $classGenerator->addPropertyFromGenerator($containerservicePool = new ServicePoolHolderProperty());
        $closure = static function (MethodGenerator $generatedMethod) use ($originalClass, $classGenerator): void {
            ClassGeneratorUtils::addMethodIfNotFinal($originalClass, $classGenerator, $generatedMethod);
        };

        \array_map(
            $closure,
            \array_merge(
                \array_map(
                    $this->methodInterceptorBuilder->buildMethodInterceptor($containerservicePool),
                    ProxiedMethodsFilter::getProxiedMethods($originalClass)
                ),
                [
                    Constructor::generateMethod($containerservicePool),
                ]
            )
        );
    }
}
