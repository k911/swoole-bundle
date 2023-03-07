<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle;

use K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass\DebugLogProcessorPass;
use K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass\StatefulServicesPass;
use K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass\StreamedResponseListenerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SwooleBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DebugLogProcessorPass());
        $container->addCompilerPass(new StreamedResponseListenerPass());
        $container->addCompilerPass(new StatefulServicesPass(), PassConfig::TYPE_BEFORE_REMOVING, -10000);
    }
}
