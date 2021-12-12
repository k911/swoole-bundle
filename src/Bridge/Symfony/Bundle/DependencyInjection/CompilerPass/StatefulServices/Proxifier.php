<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass\StatefulServices;

use Doctrine\ORM\EntityManager;
use K911\Swoole\Bridge\Doctrine\ORM\EntityManagerResetter;
use K911\Swoole\Bridge\Doctrine\ORM\EntityManagerStabilityChecker;
use K911\Swoole\Bridge\Symfony\Container\Proxy\Instantiator;
use K911\Swoole\Bridge\Symfony\Container\ServicePool;
use RuntimeException;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class Proxifier
{
    private const STABILITY_CHECKERS = [
        EntityManager::class => EntityManagerStabilityChecker::class,
    ];

    private const RESETTERS = [
        EntityManager::class => EntityManagerResetter::class,
    ];

    private $container;

    /**
     * @var array<Reference>
     */
    private $proxifiedServicePoolsRefs = [];

    /**
     * @var array<Reference>
     */
    private $resetterRefs = [];

    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
    }

    /**
     * returns new service id of the proxified service.
     */
    public function proxifyService(string $serviceId, ?array $serviceTags = null): string
    {
        if (!$this->container->has($serviceId)) {
            throw new RuntimeException(\sprintf('Service missing: %s', $serviceId));
        }

        $serviceDef = $this->prepareProxifiedService($serviceId);
        $wrappedServiceId = \sprintf('%s.swoole_coop.wrapped', $serviceId);
        $svcPoolDef = $this->prepareServicePool($wrappedServiceId, $serviceDef);
        $svcPoolServiceId = \sprintf('%s.swoole_coop.service_pool', $serviceId);
        $proxyDef = $this->prepareProxy($svcPoolServiceId, $serviceDef, $serviceTags);
        $this->prepareResetter($serviceId, $serviceDef);

        $this->container->setDefinition($svcPoolServiceId, $svcPoolDef);
        $this->container->setDefinition($serviceId, $proxyDef); // proxy swap
        $this->container->setDefinition($wrappedServiceId, $serviceDef); // old service for copying

        $this->proxifiedServicePoolsRefs[] = new Reference($svcPoolServiceId);

        return $wrappedServiceId;
    }

    public function getProxifiedServicePoolsRefs(): array
    {
        return $this->proxifiedServicePoolsRefs;
    }

    public function getResetterRefs(): array
    {
        return $this->resetterRefs;
    }

    private function prepareProxifiedService(string $serviceId): Definition
    {
        $serviceDef = $this->container->findDefinition($serviceId);
        $serviceDef->clearTag('kernel.reset');
        $serviceDef->setPublic(true);
        $serviceDef->setShared(false);

        return $serviceDef;
    }

    private function prepareServicePool(string $wrappedServiceId, Definition $serviceDef): Definition
    {
        $svcPoolDef = new Definition(ServicePool::class);
        $svcPoolDef->setShared(true);
        $svcPoolDef->setArgument('$wrappedServiceId', $wrappedServiceId);
        $svcPoolDef->setArgument('$container', new Reference('service_container'));
        $serviceClass = $serviceDef->getClass();

        if (!isset(self::STABILITY_CHECKERS[$serviceClass])) {
            return $svcPoolDef;
        }

        $checkerClass = self::STABILITY_CHECKERS[$serviceClass];
        $this->container->findDefinition($checkerClass);
        $svcPoolDef->setArgument('$stabilityChecker', new Reference($checkerClass));

        return $svcPoolDef;
    }

    private function prepareProxy(
        string $svcPoolServiceId,
        Definition $serviceDef,
        ?array $serviceTags = null
    ): Definition {
        $serviceWasPublic = $serviceDef->isPublic();
        $serviceClass = $serviceDef->getClass();
        $proxyDef = new Definition($serviceClass);
        $proxyDef->setFactory([new Reference(Instantiator::class), 'newInstance']);
        $proxyDef->setPublic($serviceWasPublic);
        $proxyDef->setArgument('$servicePool', new Reference($svcPoolServiceId));
        $proxyDef->setArgument('$wrappedSvcClass', $serviceClass);

        if (\is_array($serviceTags)) {
            $proxyDef->addTag('kernel.reset', $serviceTags[0]);
        }

        return $proxyDef;
    }

    private function prepareResetter(string $proxySvcId, Definition $serviceDef): void
    {
        $serviceClass = $serviceDef->getClass();

        if (!isset(self::RESETTERS[$serviceClass])) {
            return;
        }

        $resetterClass = self::RESETTERS[$serviceClass];
        $resetterDef = new ChildDefinition($resetterClass);
        $resetterDef->setClass($resetterClass);
        $resetterDef->addArgument(new Reference($proxySvcId));
        $this->resetterRefs[] = $resetterDef;
    }
}
