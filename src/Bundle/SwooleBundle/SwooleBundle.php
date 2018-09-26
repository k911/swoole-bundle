<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle;

use App\Bundle\SwooleBundle\DependencyInjection\Compiler\BootManagerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SwooleBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new BootManagerPass());
    }
}
