<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
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
        $rootNode = $this->builder->root('swoole');

        $rootNode
            ->children()
                ->arrayNode('http_server')
                    ->children()
                        ->scalarNode('host')
                            ->defaultValue('127.0.0.1')
                        ->end()
                        ->integerNode('port')
                            ->min(0)
                            ->max(65535)
                            ->defaultValue(9501)
                        ->end()
                        ->arrayNode('static')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->enumNode('strategy')
                                    ->defaultValue('auto')
                                    ->treatFalseLike('off')
                                    ->values(['off', 'default', 'advanced', 'auto'])
                                ->end()
                                ->scalarNode('public_dir')
                                    ->cannotBeEmpty()
                                    ->defaultValue('%kernel.project_dir%/public')
                                ->end()
                            ->end()
                        ->end() // end static
                        ->arrayNode('services')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('debug')
                                    ->defaultFalse()
                                    ->treatNullLike(false)
                                ->end()
                                ->booleanNode('trust_all_proxies')
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
        return new self(new TreeBuilder());
    }
}
