<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\DependencyInjection;

use App\Bundle\SwooleBundle\Bridge\Doctrine\ORM\EntityManagerHandler;
use App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation\TrustAllProxiesHttpServerDriver;
use App\Bundle\SwooleBundle\Bridge\Symfony\HttpKernel\DebugHttpKernelHttpServerDriver;
use App\Bundle\SwooleBundle\Server\AdvancedStaticFilesHandler;
use App\Bundle\SwooleBundle\Server\HttpServerConfiguration;
use App\Bundle\SwooleBundle\Server\HttpServerDriverInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
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

        $this->registerHttpServer($config['http_server'], $container);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    private function registerHttpServer(array $config, ContainerBuilder $container): void
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

        if (!empty($config['drivers'])) {
            $this->registerHttpServerDrivers($config['drivers'], $container);
        }
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function registerHttpServerDrivers(array $config, ContainerBuilder $container): void
    {
        if ($config['trust_all_proxies']) {
            $container->register(TrustAllProxiesHttpServerDriver::class)
                ->addArgument(new Reference(TrustAllProxiesHttpServerDriver::class.'.inner'))
                ->setAutowired(true)
                ->setPublic(false)
                ->setDecoratedService(HttpServerDriverInterface::class, null, -10);
        }

        if (
            $config['entity_manager_handler'] ||
            (null === $config['entity_manager_handler'] && \class_exists(EntityManager::class) && $container->has(EntityManagerInterface::class))
        ) {
            $container->register(EntityManagerHandler::class)
                ->addArgument(new Reference(EntityManagerHandler::class.'.inner'))
                ->setAutowired(true)
                ->setPublic(false)
                ->setDecoratedService(HttpServerDriverInterface::class, null, -20);
        }

        if ($config['debug']) {
            $container->register(DebugHttpKernelHttpServerDriver::class)
                ->addArgument(new Reference(DebugHttpKernelHttpServerDriver::class.'.inner'))
                ->setAutowired(true)
                ->setPublic(false)
                ->setDecoratedService(HttpServerDriverInterface::class, null, -50);
        }

        if ($config['advanced_static_handler']) {
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
