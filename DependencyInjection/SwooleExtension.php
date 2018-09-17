<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\DependencyInjection;

use App\Bundle\SwooleBundle\Bridge\Doctrine\ORM\EntityManagerHandler;
use App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation\CloudFrontRequestFactory;
use App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation\RequestFactoryInterface;
use App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation\TrustAllProxiesRequestHandler;
use App\Bundle\SwooleBundle\Bridge\Symfony\HttpKernel\DebugHttpKernelRequestHandler;
use App\Bundle\SwooleBundle\Server\Config\Socket;
use App\Bundle\SwooleBundle\Server\HttpServerConfiguration;
use App\Bundle\SwooleBundle\Server\RequestHandler\AdvancedStaticFilesServer;
use App\Bundle\SwooleBundle\Server\RequestHandler\RequestHandlerInterface;
use App\Bundle\SwooleBundle\Server\Runtime\BootableInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
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
        $configuration = Configuration::fromTreeBuilder();

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $container->registerForAutoconfiguration(BootableInterface::class)
            ->addTag('swoole.bootable_service');

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
        if (!empty($config['services'])) {
            $this->registerHttpServerServices($config['services'], $container);
        }

        $this->registerHttpServerConfiguration($config, $container);
    }

    private function registerHttpServerConfiguration(array $config, ContainerBuilder $container): void
    {
        [
            'static' => $static,
            'host' => $host,
            'port' => $port,
            'running_mode' => $runningMode,
            'socket_type' => $socketType,
            'ssl_enabled' => $sslEnabled,
            'settings' => $settings,
        ] = $config;

        if ('auto' === $static['strategy']) {
            $static['strategy'] = $this->isDebugOrNotProd($container) ? 'advanced' : 'off';
        }

        if ('advanced' === $static['strategy']) {
            $container->register(AdvancedStaticFilesServer::class)
                ->addArgument(new Reference(AdvancedStaticFilesServer::class.'.inner'))
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(false)
                ->setDecoratedService(RequestHandlerInterface::class, null, -60);
        }

        $settings['serve_static'] = $static['strategy'];
        $settings['public_dir'] = $static['public_dir'];

        if ('auto' === $settings['log_level']) {
            $settings['log_level'] = $container->getParameter('kernel.debug') ? 'debug' : 'notice';
        }

        $defaultSocket = new Definition(Socket::class, [$host, $port, $socketType, $sslEnabled]);
        $container->getDefinition(HttpServerConfiguration::class)
            ->addArgument($defaultSocket)
            ->addArgument($runningMode)
            ->addArgument($settings);
    }

    /**
     * Registers optional http server dependencies providing various features.
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function registerHttpServerServices(array $config, ContainerBuilder $container): void
    {
        // RequestFactoryInterface
        // -----------------------
        if ($config['cloudfront_proto_header_handler']) {
            $container->register(CloudFrontRequestFactory::class)
                ->addArgument(new Reference(CloudFrontRequestFactory::class.'.inner'))
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(false)
                ->setDecoratedService(RequestFactoryInterface::class, null, -10);
        }

        // RequestHandlerInterface
        // -------------------------
        if ($config['trust_all_proxies']) {
            $container->register(TrustAllProxiesRequestHandler::class)
                ->addArgument(new Reference(TrustAllProxiesRequestHandler::class.'.inner'))
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(false)
                ->setDecoratedService(RequestHandlerInterface::class, null, -10);
        }

        if ($config['entity_manager_handler'] || (null === $config['entity_manager_handler'] && \class_exists(EntityManager::class) && $container->has(EntityManagerInterface::class))) {
            $container->register(EntityManagerHandler::class)
                ->addArgument(new Reference(EntityManagerHandler::class.'.inner'))
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(false)
                ->setDecoratedService(RequestHandlerInterface::class, null, -20);
        }

        if ($config['debug'] || (null === $config['debug'] && $container->getParameter('kernal.debug'))) {
            $container->register(DebugHttpKernelRequestHandler::class)
                ->addArgument(new Reference(DebugHttpKernelRequestHandler::class.'.inner'))
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(false)
                ->setDecoratedService(RequestHandlerInterface::class, null, -50);
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

    private function isDebugOrNotProd(ContainerBuilder $container): bool
    {
        return $container->getParameter('kernel.debug') && 'prod' !== $container->getParameter('kernel.environment');
    }
}
