<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Doctrine;

use K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass\StatefulServices\CustomProcessor;
use K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass\StatefulServices\Proxifier;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DoctrineProcessor implements CustomProcessor
{
    public function process(ContainerBuilder $container, Proxifier $proxifier): void
    {
        /** @var array<string,string> */
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['DoctrineBundle'])) {
            return;
        }

        $entityManagers = $container->getParameter('doctrine.entity_managers');
        $newEntityManagers = [];

        foreach ($entityManagers as $name => $serviceId) {
            $emDef = $container->findDefinition($serviceId);
            $connRef = $emDef->getArgument(0);
            $proxifier->proxifyService((string) $connRef);
            $newEntityManagers[$name] = $proxifier->proxifyService($serviceId);
        }

        $container->setParameter('doctrine.entity_managers', $newEntityManagers);
    }
}
