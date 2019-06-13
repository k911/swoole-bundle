<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection;

use Assert\Assertion;
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
use K911\Swoole\Server\Config\EventCallbacks;
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
use K911\Swoole\Server\ServerInterface;
use K911\Swoole\Server\TaskHandler\TaskHandlerInterface;
use K911\Swoole\Server\TestDumpingHandler;
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
    private const SWOOLE_BUNDLE_CONFIG_TO_SWOOLE_SERVER_EVENTS = [
        'server_start' => EventCallbacks::EVENT_SERVER_START,
        'server_shutdown' => EventCallbacks::EVENT_SERVER_SHUTDOWN,
        'manager_start' => EventCallbacks::EVENT_MANAGER_START,
        'manager_stop' => EventCallbacks::EVENT_MANAGER_STOP,
        'worker_start' => EventCallbacks::EVENT_WORKER_START,
        'worker_stop' => EventCallbacks::EVENT_WORKER_STOP,
        'worker_exit' => EventCallbacks::EVENT_WORKER_EXIT,
        'worker_error' => EventCallbacks::EVENT_WORKER_ERROR,
        'task' => EventCallbacks::EVENT_TASK,
        'task_finish' => EventCallbacks::EVENT_TASK_FINISH,
        'pipe_message' => EventCallbacks::EVENT_PIPE_MESSAGE,
        'before_reload' => EventCallbacks::EVENT_BEFORE_RELOAD,
        'after_reload' => EventCallbacks::EVENT_AFTER_RELOAD,
        'connect' => EventCallbacks::EVENT_CONNECT,
        'receive' => EventCallbacks::EVENT_RECEIVE,
        'close' => EventCallbacks::EVENT_CLOSE,
        'packet' => EventCallbacks::EVENT_PACKET,
        'http_request' => EventCallbacks::EVENT_HTTP_REQUEST,
        'websocket_handshake' => EventCallbacks::EVENT_WEBSOCKET_HANDSHAKE,
        'websocket_open' => EventCallbacks::EVENT_WEBSOCKET_OPEN,
        'websocket_message' => EventCallbacks::EVENT_WEBSOCKET_MESSAGE,
    ];

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
        $loader->load('server.yaml');

        $container->registerForAutoconfiguration(BootableInterface::class)
            ->addTag('swoole_bundle.bootable_service')
        ;
        $container->registerForAutoconfiguration(ConfiguratorInterface::class)
            ->addTag('swoole_bundle.server_configurator')
        ;

        $config = $this->processConfiguration($configuration, $configs);

        if (!empty($config['server'])) {
            $this->registerServer($config['server'], $container);
        }

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

    private function configureSocketDefinition(Definition $socketDef, array $config): void
    {
        $socketDef->setArguments([
            '$host' => $config['socket']['host'],
            '$port' => $config['socket']['port'],
            '$type' => $config['socket']['type'],
            '$encryption' => $config['encryption']['enabled'],
        ]);
    }

    private function mergeListenerToConfig(array $config, array $listener): array
    {
//        $isListenerConfig = !isset($config['running_mode']);
        [
            'encryption' => $encryption,
            'http' => $http,
            'websocket' => $websocket,
        ] = $listener;

        if ($websocket['enabled']) {
            $config['open_websocket_protocol'] = true;
        }

        if ($http['enabled']) {
            $config['open_http_protocol'] = true;
            if ($http['http2']) {
                $config['open_http2_protocol'] = true;
            }
        }

        if ($encryption['enabled']) {
            /* @see swoole-src/swoole_server_port.cc#525 */
            /* @see swoole-src/swoole_runtime.cc#1007 */

            if (!empty($encryption['certificate_authority'])) {
                if (!empty($encryption['certificate_authority']['file'])) {
                    $config['ssl_cafile'] = $encryption['file'];
                } elseif (!empty($encryption['certificate_authority']['path'])) {
                    $config['ssl_capath'] = $encryption['path'];
                }
            }

            if (!empty($encryption['server_certificate'])) {
                $config['ssl_cert_file'] = $encryption['server_certificate']['file'];
                $config['ssl_key_file'] = $encryption['server_certificate']['key']['file'];
                if (!empty($encryption['server_certificate']['key']['passphrase'])) {
                    $config['ssl_passphrase'] = $encryption['server_certificate']['key']['passphrase'];
                }
            }

            if (!empty($encryption['client_certificate'])) {
                $config['ssl_client_cert_file'] = $encryption['client_certificate']['file'];
                $config['ssl_allow_self_signed'] = $encryption['client_certificate']['insecure'] ?? false;

                if ($encryption['client_certificate']['verify']['enabled']) {
                    $config['ssl_verify_peer'] = true;
                    if (!empty($encryption['client_certificate']['verify']['depth'])) {
                        $config['ssl_verify_depth'] = $encryption['client_certificate']['verify']['depth'];
                    }
                }
            }

//            $config['ssl_disable_compression'] = $listener['encryption']['xxx'];
//            $config['ssl_host_name'] = $listener['encryption']['xxx'];
            if (!empty($encryption['ciphers'])) {
                $config['ssl_ciphers'] = $encryption['ciphers'];
            }
        }

        return $config;
    }

    private function configureLazySwooleServerObjectUsingProxyManager(Definition $serverDefinition): void
    {
        if ($this->proxyManagerInstalled()) {
            $serverDefinition->setClass(ServerInterface::class)
                ->setLazy(true)
                ->setFactory([new Reference('swoole_bundle.server.factory'), 'make'])
                ->setArguments([])
            ;
        }
    }

    private function registerServer(array $server, ContainerBuilder $container): void
    {
        $serverDefinition = $container->getDefinition('swoole_bundle.server');
        if ($this->proxyManagerInstalled()) {
            $this->configureLazySwooleServerObjectUsingProxyManager($serverDefinition);
        }

        $listenerTemplates = $server['templates']['listeners'] ?? [];
        $handlerTemplates = $server['templates']['handlers'] ?? [];
        $serverHandlers = $server['handlers'] ?? [];

        $serverConfig = $server['config'] ?? [];

        $mainSocketDefinition = $container->getDefinition('swoole_bundle.server.main_socket');
        $this->configureSocketDefinition($mainSocketDefinition, $server);

        $listenersDefinition = $container->getDefinition('swoole_bundle.server.listeners');
        $handlersDefinition = $container->getDefinition('swoole_bundle.server.callbacks');

        $this->configureEventCallbacks($handlersDefinition, $container, $handlerTemplates, $serverHandlers);

        $serverConfigDefinition = $container->getDefinition('swoole_bundle.server.config');

        $serverConfigDefinition->setArguments([
            '$runningMode' => $server['running_mode'],
            '$config' => $this->mergeListenerToConfig($serverConfig, $server),
        ]);
    }

    private function configureEventCallbacks(Definition $eventCallbacks, ContainerBuilder $container, array $handlerTemplates, array $handlersMap): void
    {
        foreach ($handlersMap as $eventName => $handlers) {
            foreach ($this->prepareDefinitions($handlers, $container, 'swoole_bundle.server.handlers', $handlerTemplates) as $definitionConfig) {
                /**
                 * @var string     $id
                 * @var Definition $definition
                 * @var array      $config
                 */
                [
                    $id,
                    $definition,
                    $config,
                ] = $definitionConfig;
                $this->configureHandler($eventName, $definition, $config);
                $eventCallbacks->addMethodCall('register', [
                    self::SWOOLE_BUNDLE_CONFIG_TO_SWOOLE_SERVER_EVENTS[$eventName],
                    [new Reference($id), 'handle'],
                    $config['priority'],
                ]);
            }
        }
    }

    // special configuration for different handlers
    private function configureHandler(string $eventName, Definition $handler, array $config): void
    {
        switch ($handler->getClass()) {
            case TestDumpingHandler::class:
                $handler->addMethodCall('setText', [$eventName]);
                // no break
            default:
                // noop
        }
    }

    private function filterDefinitionId(string $definitionId): string
    {
        if (0 === \mb_strpos($definitionId, '@')) {
            return \mb_substr($definitionId, 1, \mb_strlen($definitionId));
        }

        return $definitionId;
    }

    private function copyDefinition(ContainerBuilder $container, string $definitionId, string $newDefinitionId): Definition
    {
        return $container->setDefinition($newDefinitionId,
            $container->getDefinition($definitionId)
        );
    }

    private function prepareDefinitionWithParent(array $definition, array $parent, ContainerBuilder $container, string $idPrefix, int $counter): array
    {
        $id = \sprintf('service_%d', $counter);
        $parentName = $definition['parent'];
        if (!empty($definition['id'])) {
            $id = $this->filterDefinitionId($definition['id']);
            Assertion::false($container->has($id), \sprintf('Service "%s" cannot be used in service group "%s" when parent "%s" is defined', $id, $idPrefix, $parentName));
        }

        $id = \sprintf('%s.%s', $idPrefix, $id);
        Assertion::false($container->has($id), \sprintf('Service ID "%s" cannot be used in service group "%s" with parent "%s", because ID has been already registered in container', $id, $idPrefix, $parentName));

        $templateId = $this->filterDefinitionId($parent['id']);
        Assertion::true($container->has($templateId), \sprintf('Service template "%s" has defined invalid template service ID "%s", because it does not exist in container', $parentName, $templateId));
        $serviceDefinition = $this->copyDefinition($container, $templateId, $id);

        return [$id, $serviceDefinition, $definition];
    }

    private function prepareLoneDefinition(array $definition, ContainerBuilder $container): array
    {
        Assertion::notEmptyKey($definition, 'id');
        $id = $this->filterDefinitionId($definition['id']);
        $serviceDefinition = $container->getDefinition($id);

        return [$id, $serviceDefinition, $definition];
    }

    private function prepareDefinitions(array $children, ContainerBuilder $container, string $idPrefix = 'swoole_bundle.server.listeners', array $predefinedParents = []): \Generator
    {
        $generatedIdCounter = 0;
        foreach ($children as $child) {
            if (!empty($child['parent'])) {
                ++$generatedIdCounter;
                Assertion::notEmptyKey($predefinedParents, $child['parent'], \sprintf('Template "%%s" could not be found for service group "%s"', $idPrefix));
                yield $this->prepareDefinitionWithParent($child, $predefinedParents[$child['parent']], $container, $idPrefix, $generatedIdCounter);
            } else {
                yield $this->prepareLoneDefinition($child, $container);
            }
        }
    }

    private function deepRecursiveNotEmptyMerge(array $base, array $override): array
    {
        $result = $base;

        foreach ($override as $key => $value) {
            if (empty($value)) {
                continue;
            }

            if (\is_array($value)) {
                $result[$key] = isset($base[$key]) && \is_array($base[$key]) ? $this->deepRecursiveNotEmptyMerge($base[$key], $value) : $value;

                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private function proxyManagerInstalled(): bool
    {
        // If symfony/proxy-manager-bridge is installed this class exists
        return \class_exists('\Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator');
    }
}
