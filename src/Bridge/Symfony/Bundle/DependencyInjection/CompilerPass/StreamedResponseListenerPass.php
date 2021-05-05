<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass;

use K911\Swoole\Bridge\Symfony\HttpFoundation\StreamedResponseListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Replaces Symfony's native StreamedResponseListener with a custom one compatible with Swoole.
 */
final class StreamedResponseListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $oldListenerDefinition = null;
        $definitionId = 'streamed_response_listener';
        $oldDefinitionId = \sprintf('%s.original', $definitionId);

        if ($container->hasDefinition('streamed_response_listener')) {
            $oldListenerDefinition = $container->getDefinition('streamed_response_listener');
            $oldListenerDefinition->clearTag('kernel.event_subscriber');
            $container->setDefinition($oldDefinitionId, $oldListenerDefinition);
        }

        $newDefinition = new Definition(StreamedResponseListener::class);
        $newDefinition->setAutoconfigured(true);
        $newDefinition->addTag('kernel.event_subscriber');

        if (null !== $oldListenerDefinition) {
            $newDefinition->setArgument('$delegate', new Reference($oldDefinitionId));
        }

        $container->setDefinition($definitionId, $newDefinition);
    }
}
