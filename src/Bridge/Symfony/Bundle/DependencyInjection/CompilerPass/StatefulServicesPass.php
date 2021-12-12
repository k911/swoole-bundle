<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass;

use K911\Swoole\Bridge\Doctrine\DoctrineProcessor;
use K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass\StatefulServices\CustomProcessor;
use K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass\StatefulServices\Proxifier;
use K911\Swoole\Bridge\Symfony\Container\ServicePoolContainer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class StatefulServicesPass implements CompilerPassInterface
{
    private const IGNORED_SERVICES = [
        ServicePoolContainer::class => true,
    ];

    private const MANDATORRY_SERVICES_TO_PROXIFY = [
        'annotations.reader' => null,
    ];

    private const CUSTOM_PROCESSORS = [
        DoctrineProcessor::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('swoole_bundle.cooperative_scheduling.enabled')) {
            return;
        }

        if (!$container->getParameter('swoole_bundle.cooperative_scheduling.enabled')) {
            return;
        }

        $servicesToProxify = $container->findTaggedServiceIds('kernel.reset');
        $servicesToProxify = \array_merge($servicesToProxify, self::MANDATORRY_SERVICES_TO_PROXIFY);
        $proxifier = new Proxifier($container);

        foreach ($servicesToProxify as $serviceId => $tags) {
            if (isset(self::IGNORED_SERVICES[$serviceId])) {
                continue;
            }

            $proxifier->proxifyService($serviceId, $tags);
        }

        foreach (self::CUSTOM_PROCESSORS as $processorClass) {
            /** @var CustomProcessor $processor */
            $processor = new $processorClass();
            $processor->process($container, $proxifier);
        }

        $poolContainerDef = $container->findDefinition(ServicePoolContainer::class);
        $poolContainerDef->setArgument(0, $proxifier->getProxifiedServicePoolsRefs());
        $poolContainerDef->setArgument(1, $proxifier->getResetterRefs());
    }
}
