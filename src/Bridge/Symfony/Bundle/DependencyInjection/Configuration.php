<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection;

use function K911\Swoole\decode_string_as_set;
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
        $rootNode = \method_exists($this->builder, 'getRootNode') ?
            $this->builder->getRootNode() :
            $this->builder->root(self::CONFIG_NAME);

        $rootNode
            ->children()
                ->arrayNode('http_server')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('host')
                            ->cannotBeEmpty()
                            ->defaultValue('127.0.0.1')
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
                                ->integerNode('worker_count')
                                    ->min(1)
                                ->end()
                                ->integerNode('reactor_count')
                                    ->min(1)
                                ->end()
                            ->end()
                        ->end() // settings
                    ->end()
                ->end() // server
            ->end()
        ;

        return $this->builder;
    }

    public static function fromTreeBuilder(): self
    {
        return new self(new TreeBuilder(self::CONFIG_NAME));
    }
}
