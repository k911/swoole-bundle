<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container\Proxy;

use K911\Swoole\Bridge\Symfony\Container\Proxy\Generation\AccessInterceptorServicePoolFactory;
use ProxyManager\Configuration;
use ProxyManager\Version;

final class Generator extends AccessInterceptorServicePoolFactory
{
    /**
     * Cached checked class names.
     *
     * @var array<class-string>
     */
    private array $checkedClasses = [];

    public function __construct(Configuration $configuration)
    {
        parent::__construct($configuration);
    }

    /**
     * this override method activates the proxy manage class autoloader, which is kind of pain in the ass
     * to activate in Symfony, since Symfony relies directly on Composer and it would be needed to register this
     * autoloader with Composer autoloader initialization.
     *
     * @psalm-template RealObjectType of object
     * @psalm-param class-string<RealObjectType> $className
     *
     * @param array<string, mixed> $proxyOptions @codingStandardsIgnoreLine
     * @psalm-return class-string<RealObjectType>
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function generateProxy(string $className, array $proxyOptions = []): string
    {
        if (\array_key_exists($className, $this->checkedClasses)) {
            $generatedClassName = $this->checkedClasses[$className];
            \assert(\is_a($generatedClassName, $className, true));

            return $generatedClassName;
        }

        $proxyParameters = [
            'className' => $className,
            'factory' => self::class,
            'proxyManagerVersion' => Version::getVersion(),
            'proxyOptions' => $proxyOptions,
        ];
        $proxyClassName = $this
            ->configuration
            ->getClassNameInflector()
            ->getProxyClassName($className, $proxyParameters)
        ;

        if (\class_exists($proxyClassName)) {
            return $this->checkedClasses[$className] = $proxyClassName;
        }

        $autoloader = $this->configuration->getProxyAutoloader();

        if ($autoloader($proxyClassName)) {
            return $this->checkedClasses[$className] = $proxyClassName;
        }

        return $this->checkedClasses[$className] = parent::generateProxy($className, $proxyOptions);
    }
}
