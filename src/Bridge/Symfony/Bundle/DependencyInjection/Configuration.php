<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection;

use function K911\Swoole\decode_string_as_set;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public const DEFAULT_PUBLIC_DIR = '%kernel.project_dir%/public';

    private const CONFIG_NAME = 'swoole';

    private $builder;

    public function __construct(TreeBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $children = $this->builder->getRootNode()->children();

        $this->addServerConfigChild($children);
        $this->addHttpServerConfigChild($children);

        $children->end();

        return $this->builder;
    }

    public static function fromTreeBuilder(): self
    {
        $treeBuilderClass = TreeBuilder::class;

        return new self(new $treeBuilderClass(self::CONFIG_NAME));
    }

    private function addServerSocketConfigChild(NodeBuilder $builder): void
    {
        $builder->arrayNode('socket')
            ->children()
                ->scalarNode('host')
                    ->cannotBeEmpty()
                    ->defaultValue('0.0.0.0')
                ->end()
                ->scalarNode('port')
                    ->cannotBeEmpty()
                    ->defaultValue(9501)
                ->end()
                ->enumNode('type')
                    ->values(['tcp', 'tcp_ipv6', 'tcp_dualstack', 'udp', 'udp_ipv6', 'udp_dualstack', 'unix_dgram', 'unix_stream'])
                    ->cannotBeEmpty()
                    ->defaultValue('tcp_dualstack')
                ->end()
            ->end()
        ->end()
        ;
    }

    private function addServerHandlerConfigChildrenPrototype(NodeBuilder $builder): void
    {
        $builder->scalarNode('id')
            ->defaultNull()
        ->end()
        ->scalarNode('priority')
            ->defaultValue(100)
        ->end()
        ->arrayNode('config')
            ->ignoreExtraKeys(false)
            ->children()
            ->end()
        ->end()
        ;
    }

    private function addServerHandlerConfigChildren(string $eventName, NodeBuilder $builder): void
    {
        $prototypeChildren = $builder->arrayNode($eventName)
            ->arrayPrototype()
                ->beforeNormalization()
                    ->ifString()
                    ->then(function ($v): array {
                        $str = (string) $v;
                        if (0 === \mb_strpos($str, '@')) {
                            return ['id' => $str];
                        }

                        return ['parent' => $str];
                    })
                ->end()
                ->children()
                    ->scalarNode('parent')
                        ->defaultNull()
                    ->end()
        ;

        $this->addServerHandlerConfigChildrenPrototype($prototypeChildren);

        $prototypeChildren
                ->end()
            ->end()
        ->end()
        ;
    }

    private function addServerTemplatesConfig(NodeBuilder $builder): void
    {
        $listenersChildrenPrototype = $builder->arrayNode('templates')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('listeners')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->addDefaultsIfNotSet()
                        ->children()
        ;

        $this->addServerListenerConfigChildren($listenersChildrenPrototype, false);

        $handlersChildrenPrototype = $listenersChildrenPrototype
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('handlers')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->addDefaultsIfNotSet()
                        ->children()
        ;

        $this->addServerHandlerConfigChildrenPrototype($handlersChildrenPrototype);

        $handlersChildrenPrototype
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end()
        ;
    }

    private function addServerListenerConfigChildren(NodeBuilder $builder, bool $listener = false): void
    {
        $this->addServerSocketConfigChild($builder);

        $builder->scalarNode('id')
            ->defaultNull()
        ->end()
        ;

        $builder->arrayNode('websocket')
            ->canBeEnabled()
            ->children()
            ->end()
        ->end()
        ;

        $builder->arrayNode('http')
            ->canBeEnabled()
            ->children()
                ->booleanNode('http2')
                    ->defaultFalse()
                ->end()
            ->end()
        ->end()
        ;

        $builder->arrayNode('encryption')
            ->ignoreExtraKeys(false)
            ->canBeEnabled()
            ->children()
                ->arrayNode('certificate_authority')
                    ->children()
                        ->scalarNode('file')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('path')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('server_certificate')
                    ->children()
                        ->scalarNode('file')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('key')
                            ->children()
                                ->scalarNode('file')
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('passphrase')
                                    ->defaultNull()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('client_certificate')
                    ->children()
                        ->scalarNode('file')
                            ->defaultNull()
                        ->end()
                        ->booleanNode('insecure')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('verify')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('depth')
                                    ->defaultNull()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('ciphers')
                    ->defaultNull()
                ->end()
            ->end()
        ->end()
        ;

        $builder->arrayNode('config')
            ->ignoreExtraKeys(false)
            ->children()
            ->end()
        ->end()
        ;

        $handlersChildren = $builder->arrayNode('handlers')
            ->children()
        ;

        if (!$listener) {
            $this->addServerHandlerConfigChildren('server_start', $handlersChildren);
            $this->addServerHandlerConfigChildren('server_shutdown', $handlersChildren);
            $this->addServerHandlerConfigChildren('manager_start', $handlersChildren);
            $this->addServerHandlerConfigChildren('manager_stop', $handlersChildren);
            $this->addServerHandlerConfigChildren('worker_start', $handlersChildren);
            $this->addServerHandlerConfigChildren('worker_stop', $handlersChildren);
            $this->addServerHandlerConfigChildren('worker_exit', $handlersChildren);
            $this->addServerHandlerConfigChildren('worker_error', $handlersChildren);
            $this->addServerHandlerConfigChildren('task', $handlersChildren);
            $this->addServerHandlerConfigChildren('task_finish', $handlersChildren);
            $this->addServerHandlerConfigChildren('pipe_message', $handlersChildren);
            // swoole version >= 4.5.0
            $this->addServerHandlerConfigChildren('before_reload', $handlersChildren);
            $this->addServerHandlerConfigChildren('after_reload', $handlersChildren);
        }

        /* @see swoole-src/swoole_server_port.cc#685 */
        $this->addServerHandlerConfigChildren('connect', $handlersChildren);
        $this->addServerHandlerConfigChildren('receive', $handlersChildren);
        $this->addServerHandlerConfigChildren('close', $handlersChildren);
        $this->addServerHandlerConfigChildren('packet', $handlersChildren);
        $this->addServerHandlerConfigChildren('http_request', $handlersChildren);
        $this->addServerHandlerConfigChildren('websocket_handshake', $handlersChildren);
        $this->addServerHandlerConfigChildren('websocket_open', $handlersChildren);
        $this->addServerHandlerConfigChildren('websocket_message', $handlersChildren);
        // TODO: buffer_full / buffer_empty?

        $handlersChildren
            ->end()
        ->end()
        ;
    }

    private function addServerConfigChild(NodeBuilder $builder): void
    {
        $serverChildren = $builder->arrayNode('server')
            ->children()
        ;

        $serverChildren->enumNode('running_mode')
            ->cannotBeEmpty()
            ->defaultValue('process')
            ->values(['reactor', 'process'])
        ->end()
        ;

        $this->addServerTemplatesConfig($serverChildren);

        $this->addServerSocketConfigChild($serverChildren);

        $serverChildren->scalarNode('parent')
            ->defaultValue('http')
        ->end()
        ;

        $this->addServerListenerConfigChildren($serverChildren, false);

        $listenersChildren = $serverChildren->arrayNode('listeners')
            ->ignoreExtraKeys(false)
            ->arrayPrototype()
                ->children()
                    ->scalarNode('parent')
                        ->defaultValue(null)
                    ->end()
        ;

        $this->addServerListenerConfigChildren($listenersChildren, true);

        $listenersChildren
                ->end()
            ->end()
        ->end()
        ;

        $serverChildren
            ->end()
        ->end()
        ;
    }

    private function addHttpServerConfigChild(NodeBuilder $builder): void
    {
        $builder->arrayNode('http_server')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('host')
                    ->cannotBeEmpty()
                    ->defaultValue('0.0.0.0')
                ->end()
                ->scalarNode('port')
                    ->cannotBeEmpty()
                    ->defaultValue(9501)
                ->end()
                ->arrayNode('trusted_hosts')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v): array { return decode_string_as_set($v); })
                    ->end()
                ->end()
                ->arrayNode('trusted_proxies')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v): array { return decode_string_as_set($v); })
                    ->end()
                ->end()
                ->enumNode('running_mode')
                    ->cannotBeEmpty()
                    ->defaultValue('process')
                    ->values(['process', 'reactor', 'thread'])
                ->end()
                ->enumNode('socket_type')
                    ->cannotBeEmpty()
                    ->defaultValue('tcp')
                    ->values(['tcp', 'tcp_ipv6', 'udp', 'udp_ipv6', 'unix_dgram', 'unix_stream'])
                ->end()
                ->booleanNode('ssl_enabled')
                    ->defaultFalse()
                    ->treatNullLike(false)
                ->end()
                ->enumNode('hmr')
                    ->cannotBeEmpty()
                    ->defaultValue('auto')
                    ->treatFalseLike('off')
                    ->values(['off', 'auto', 'inotify'])
                ->end()
                ->arrayNode('api')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()
                        ->ifTrue(function ($v): bool {
                            return \is_string($v) || \is_bool($v) || \is_numeric($v) || null === $v;
                        })
                        ->then(function ($v): array {
                            return [
                                'enabled' => (bool) $v,
                                'host' => '0.0.0.0',
                                'port' => 9200,
                            ];
                        })
                    ->end()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('host')
                            ->cannotBeEmpty()
                            ->defaultValue('0.0.0.0')
                        ->end()
                        ->scalarNode('port')
                            ->cannotBeEmpty()
                            ->defaultValue(9200)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('static')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v): array {
                            return [
                                'strategy' => $v,
                                'public_dir' => 'off' === $v ? null : self::DEFAULT_PUBLIC_DIR,
                            ];
                        })
                    ->end()
                    ->children()
                        ->enumNode('strategy')
                            ->defaultValue('auto')
                            ->treatFalseLike('off')
                            ->values(['off', 'default', 'advanced', 'auto'])
                        ->end()
                        ->scalarNode('public_dir')
                            ->defaultValue(self::DEFAULT_PUBLIC_DIR)
                        ->end()
                    ->end()
                ->end() // end static
                ->arrayNode('exception_handler')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v): array {
                            return [
                                'type' => $v,
                                'verbosity' => 'auto',
                                'handler_id' => null,
                            ];
                        })
                    ->end()
                    ->children()
                        ->enumNode('type')
                            ->cannotBeEmpty()
                            ->defaultValue('auto')
                            ->treatFalseLike('auto')
                            ->values(['json', 'production', 'custom', 'auto'])
                        ->end()
                        ->enumNode('verbosity')
                            ->cannotBeEmpty()
                            ->defaultValue('auto')
                            ->treatFalseLike('auto')
                            ->values(['trace', 'verbose', 'default', 'auto'])
                        ->end()
                        ->scalarNode('handler_id')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end() // end exception_handler
                ->arrayNode('services')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('debug_handler')
                            ->defaultNull()
                        ->end()
                        ->booleanNode('trust_all_proxies_handler')
                            ->defaultFalse()
                            ->treatNullLike(false)
                        ->end()
                        ->booleanNode('cloudfront_proto_header_handler')
                            ->defaultFalse()
                            ->treatNullLike(false)
                        ->end()
                        ->booleanNode('entity_manager_handler')
                            ->defaultNull()
                        ->end()
                        ->booleanNode('blackfire_profiler')
                            ->defaultNull()
                        ->end()
                        ->booleanNode('session_cookie_event_listener')
                            ->defaultFalse()
                            ->treatNullLike(false)
                        ->end()
                    ->end()
                ->end() // drivers
                ->arrayNode('settings')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('log_file')
                            ->cannotBeEmpty()
                            ->defaultValue('%kernel.logs_dir%/swoole_%kernel.environment%.log')
                        ->end()
                        ->enumNode('log_level')
                            ->cannotBeEmpty()
                            ->values(['auto', 'debug', 'trace', 'info', 'notice', 'warning', 'error'])
                            ->defaultValue('auto')
                        ->end()
                        ->scalarNode('pid_file')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('buffer_output_size')
                            ->defaultValue(2097152)
                        ->end()
                        ->scalarNode('package_max_length')
                            ->defaultValue(8388608)
                        ->end()
                        ->integerNode('worker_count')
                            ->min(1)
                        ->end()
                        ->integerNode('reactor_count')
                            ->min(1)
                        ->end()
                        ->scalarNode('task_worker_count')
                            ->defaultNull()
                        ->end()
                        ->integerNode('worker_max_request')
                            ->min(0)
                            ->defaultValue(0)
                        ->end()
                        ->scalarNode('worker_max_request_grace')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end() // settings
            ->end() // children
        ->end() // http_server
        ;
    }
}
