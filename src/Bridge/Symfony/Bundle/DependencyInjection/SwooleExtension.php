<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection;

use Doctrine\ORM\EntityManagerInterface;
use K911\Swoole\Bridge\Doctrine\ORM\EntityManagerHandler;
use K911\Swoole\Bridge\Symfony\HttpFoundation\CloudFrontRequestFactory;
use K911\Swoole\Bridge\Symfony\HttpFoundation\RequestFactoryInterface;
use K911\Swoole\Bridge\Symfony\HttpFoundation\Session\SetSessionCookieEventListener;
use K911\Swoole\Bridge\Symfony\HttpFoundation\TrustAllProxiesRequestHandler;
use K911\Swoole\Bridge\Symfony\HttpKernel\DebugHttpKernelRequestHandler;
use K911\Swoole\Bridge\Symfony\Messenger\SwooleServerTaskTransportFactory;
use K911\Swoole\Bridge\Symfony\Messenger\SwooleServerTaskTransportHandler;
use K911\Swoole\Bridge\Upscale\Blackfire\WithProfiler;
use K911\Swoole\Server\Config\Socket;
use K911\Swoole\Server\Config\Sockets;
use K911\Swoole\Server\Configurator\ConfiguratorInterface;
use K911\Swoole\Server\HttpServer;
use K911\Swoole\Server\HttpServerConfiguration;
use K911\Swoole\Server\RequestHandler\AdvancedStaticFilesServer;
use K911\Swoole\Server\RequestHandler\ExceptionHandler\ExceptionHandlerInterface;
use K911\Swoole\Server\RequestHandler\ExceptionHandler\JsonExceptionHandler;
use K911\Swoole\Server\RequestHandler\ExceptionHandler\ProductionExceptionHandler;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use K911\Swoole\Server\Runtime\BootableInterface;
use K911\Swoole\Server\Runtime\HMR\HotModuleReloaderInterface;
use K911\Swoole\Server\Runtime\HMR\InotifyHMR;
use K911\Swoole\Server\TaskHandler\TaskHandlerInterface;
use K911\Swoole\Server\WorkerHandler\HMRWorkerStartHandler;
use K911\Swoole\Server\WorkerHandler\WorkerStartHandlerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Upscale\Swoole\Blackfire\Profiler;

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
        $loader->load('commands.yaml');

        $container->registerForAutoconfiguration(BootableInterface::class)
            ->addTag('swoole_bundle.bootable_service')
        ;
        $container->registerForAutoconfiguration(ConfiguratorInterface::class)
            ->addTag('swoole_bundle.server_configurator')
        ;

        $config = $this->processConfiguration($configuration, $configs);

        $this->registerHttpServer($config['http_server'], $container);

        if (\interface_exists(TransportFactoryInterface::class)) {
            $this->registerSwooleServerTransportConfiguration($container);
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

    /**
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    private function registerHttpServer(array $config, ContainerBuilder $container): void
    {
        $this->registerHttpServerServices($config['services'], $container);
        $this->registerExceptionHandler($config['exception_handler'], $container);

        $container->setParameter('swoole.http_server.trusted_proxies', $config['trusted_proxies']);
        $container->setParameter('swoole.http_server.trusted_hosts', $config['trusted_hosts']);
        $container->setParameter('swoole.http_server.api.host', $config['api']['host']);
        $container->setParameter('swoole.http_server.api.port', $config['api']['port']);

        $this->registerHttpServerConfiguration($config, $container);
    }

    private function registerExceptionHandler(array $config, ContainerBuilder $container): void
    {
        [
            'handler_id' => $handlerId,
            'type' => $type,
            'verbosity' => $verbosity,
        ] = $config;

        if ('auto' === $type) {
            $type = $this->isProd($container) ? 'production' : 'json';
        }

        switch ($type) {
            case 'json':
                $class = JsonExceptionHandler::class;

                break;
            case 'custom':
                $class = $handlerId;

                break;
            default: // case 'production'
                $class = ProductionExceptionHandler::class;

                break;
        }

        $container->setAlias(ExceptionHandlerInterface::class, $class);

        if ('auto' === $verbosity) {
            if ($this->isProd($container)) {
                $verbosity = 'production';
            } elseif ($this->isDebug($container)) {
                $verbosity = 'trace';
            } else {
                $verbosity = 'verbose';
            }
        }

        $container->getDefinition(JsonExceptionHandler::class)
            ->setArgument('$verbosity', $verbosity)
        ;
    }

    private function registerSwooleServerTransportConfiguration(ContainerBuilder $container): void
    {
        $container->register(SwooleServerTaskTransportFactory::class)
            ->addTag('messenger.transport_factory')
            ->addArgument(new Reference(HttpServer::class))
        ;

        $container->register(SwooleServerTaskTransportHandler::class)
            ->addArgument(new Reference(MessageBusInterface::class))
            ->addArgument(new Reference(SwooleServerTaskTransportHandler::class.'.inner'))
            ->setDecoratedService(TaskHandlerInterface::class, null, -10)
        ;
    }

    private function registerHttpServerConfiguration(array $config, ContainerBuilder $container): void
    {
        [
            'static' => $static,
            'api' => $api,
            'hmr' => $hmr,
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
                ->addArgument(new Reference(HttpServerConfiguration::class))
                ->addTag('swoole_bundle.bootable_service')
                ->setDecoratedService(RequestHandlerInterface::class, null, -60)
            ;
        }

        $settings['serve_static'] = $static['strategy'];
        $settings['public_dir'] = $static['public_dir'];

        if ('auto' === $settings['log_level']) {
            $settings['log_level'] = $this->isDebug($container) ? 'debug' : 'notice';
        }

        if ('auto' === $hmr) {
            $hmr = $this->resolveAutoHMR();
        }

        $sockets = $container->getDefinition(Sockets::class)
            ->addArgument(new Definition(Socket::class, [$host, $port, $socketType, $sslEnabled]))
        ;

        if ($api['enabled']) {
            $sockets->addArgument(new Definition(Socket::class, [$api['host'], $api['port']]));
        }

        $container->getDefinition(HttpServerConfiguration::class)
            ->addArgument(new Reference(Sockets::class))
            ->addArgument($runningMode)
            ->addArgument($settings)
        ;

        $this->registerHttpServerHMR($hmr, $container);
    }

    private function registerHttpServerHMR(string $hmr, ContainerBuilder $container): void
    {
        if ('off' === $hmr || !$this->isDebug($container)) {
            return;
        }

        if ('inotify' === $hmr) {
            $container->register(HotModuleReloaderInterface::class, InotifyHMR::class)
                ->addTag('swoole_bundle.bootable_service')
            ;
        }

        $container->autowire(HMRWorkerStartHandler::class)
            ->setPublic(false)
            ->setAutoconfigured(true)
            ->setArgument('$decorated', new Reference(HMRWorkerStartHandler::class.'.inner'))
            ->setDecoratedService(WorkerStartHandlerInterface::class)
        ;
    }

    private function resolveAutoHMR(): string
    {
        if (\extension_loaded('inotify')) {
            return 'inotify';
        }

        return 'off';
    }

    /**
     * Registers optional http server dependencies providing various features.
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
                ->setDecoratedService(RequestFactoryInterface::class, null, -10)
            ;
        }

        // RequestHandlerInterface
        // -------------------------
        if ($config['trust_all_proxies_handler']) {
            $container->register(TrustAllProxiesRequestHandler::class)
                ->addArgument(new Reference(TrustAllProxiesRequestHandler::class.'.inner'))
                ->addTag('swoole_bundle.bootable_service')
                ->setDecoratedService(RequestHandlerInterface::class, null, -10)
            ;
        }

        if ($config['entity_manager_handler'] || (null === $config['entity_manager_handler'] && \interface_exists(EntityManagerInterface::class) && $this->isBundleLoaded($container, 'doctrine'))) {
            $container->register(EntityManagerHandler::class)
                ->addArgument(new Reference(EntityManagerHandler::class.'.inner'))
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(false)
                ->setDecoratedService(RequestHandlerInterface::class, null, -20)
            ;
        }

        if ($config['debug_handler'] || (null === $config['debug_handler'] && $this->isDebug($container))) {
            $container->register(DebugHttpKernelRequestHandler::class)
                ->addArgument(new Reference(DebugHttpKernelRequestHandler::class.'.inner'))
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(false)
                ->setDecoratedService(RequestHandlerInterface::class, null, -50)
            ;
        }

        if ($config['session_cookie_event_listener']) {
            $container->register(SetSessionCookieEventListener::class)
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(false)
            ;
        }

        if ($config['blackfire_profiler'] || (null === $config['blackfire_profiler'] && \class_exists(Profiler::class))) {
            $container->register(Profiler::class)
                ->setClass(Profiler::class)
            ;

            $container->register(WithProfiler::class)
                ->setClass(WithProfiler::class)
                ->setAutowired(false)
                ->setAutoconfigured(false)
                ->setPublic(false)
                ->addArgument(new Reference(Profiler::class))
            ;
            $def = $container->getDefinition('swoole_bundle.server.http_server.configurator.for_server_run_command');
            $def->addArgument(new Reference(WithProfiler::class));
            $def = $container->getDefinition('swoole_bundle.server.http_server.configurator.for_server_start_command');
            $def->addArgument(new Reference(WithProfiler::class));
        }
    }

    private function isBundleLoaded(ContainerBuilder $container, string $bundleName): bool
    {
        $bundles = $container->getParameter('kernel.bundles');

        $bundleNameOnly = \str_replace('bundle', '', \mb_strtolower($bundleName));
        $fullBundleName = \ucfirst($bundleNameOnly).'Bundle';

        return isset($bundles[$fullBundleName]);
    }

    private function isProd(ContainerBuilder $container): bool
    {
        return 'prod' === $container->getParameter('kernel.environment');
    }

    private function isDebug(ContainerBuilder $container): bool
    {
        return $container->getParameter('kernel.debug');
    }

    private function isDebugOrNotProd(ContainerBuilder $container): bool
    {
        return $this->isDebug($container) || !$this->isProd($container);
    }
}
