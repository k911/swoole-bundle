<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass;

use K911\Swoole\Bridge\Symfony\HttpFoundation\StreamedResponseListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Replaces Symfony's native StreamedResponseListener with a custom one compatible with Swoole.
 */
final class StreamedResponseListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('streamed_response_listener')) {
            $definition = $container->getDefinition('streamed_response_listener');
            $definition
                ->setClass(StreamedResponseListener::class)
                ->setAutowired(true)
            ;
        } else {
            $container
                ->autowire('streamed_response_listener', StreamedResponseListener::class)
                ->setAutoconfigured(true)
            ;
        }
    }
}
