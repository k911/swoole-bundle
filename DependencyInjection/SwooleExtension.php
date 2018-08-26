<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\DependencyInjection;

use App\Bundle\SwooleBundle\Server\AdvancedStaticFilesHandler;
use App\Bundle\SwooleBundle\Server\HttpServerConfiguration;
use App\Bundle\SwooleBundle\Server\HttpServerDriverInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class SwooleExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = Configuration::fromTreeBuilder();
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerServer($config['server'], $container);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    private function registerServer(array $config, ContainerBuilder $container): void
    {
        $container->getDefinition('app.swoole.server.http_server.server_instance')
            ->addArgument($config['host'])
            ->addArgument($config['port'])
            ->addArgument(SWOOLE_BASE)
            ->addArgument(SWOOLE_TCP);

        $container->getDefinition(HttpServerConfiguration::class)
            ->addArgument($config['host'])
            ->addArgument($config['port'])
            ->addArgument($config['settings'] ?? []);

        if (true === $config['use_advanced_static_handler']) {
            $container->register(AdvancedStaticFilesHandler::class)
                ->addArgument(new Reference(AdvancedStaticFilesHandler::class.'.inner'))
                ->setAutowired(true)
                ->setPublic(false)
                ->setDecoratedService(HttpServerDriverInterface::class, null, -60);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'swoole';
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return Configuration::fromTreeBuilder();
    }
}
