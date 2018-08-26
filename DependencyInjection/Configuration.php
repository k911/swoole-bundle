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
                ->arrayNode('server')
                    ->children()
                        ->scalarNode('host')
                            ->defaultValue('127.0.0.1')
                        ->end()
                        ->integerNode('port')
                            ->min(0)
                            ->max(65535)
                            ->defaultValue(9501)
                        ->end()
                        ->booleanNode('use_advanced_static_handler')
                            ->defaultFalse()
                            ->treatNullLike(false)
                        ->end()
                        ->arrayNode('settings')
                            ->children()
                                ->booleanNode('serve_static_files')
                                    ->defaultFalse()
                                    ->treatNullLike(false)
                                ->end()
                                ->scalarNode('public_dir')
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
