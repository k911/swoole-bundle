<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection;

use function K911\Swoole\decode_string_as_set;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

final class Configuration implements ConfigurationInterface
{
    public const DEFAULT_PUBLIC_DIR = '%kernel.project_dir%/public';
    public const DEFAULT_WATCH_DIR = '%kernel.project_dir%/';

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
        $this->builder->getRootNode()
            ->children()
                ->arrayNode('http_server')
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
                        ->arrayNode('hmr')
                            ->addDefaultsIfNotSet()
                            ->beforeNormalization()
                                ->ifTrue(function ($v): bool {
                                    return \is_string($v) || \is_bool($v) || \is_numeric($v) || null === $v;
                                })
                                ->then(function ($v): array {
                                    return [
                                        'enabled' => \in_array($v, ['auto', 'inotify', 'fsnotify'], true),
                                        'type' => \in_array($v, ['auto', 'inotify', 'fsnotify'], true) ? $v : 'auto',
                                        'watch_dir' => self::DEFAULT_WATCH_DIR,
                                        'verbose_output' => false,
                                        'tick_duration' => 5,
                                    ];
                                })
                            ->end()
                            ->children()
                                ->booleanNode('enabled')
                                    ->defaultFalse()
                                ->end()
                                ->enumNode('type')
                                    ->defaultValue('auto')
                                    ->values(['auto', 'inotify', 'fsnotify'])
                                ->end()
                                ->integerNode('tick_duration')
                                    ->min(0)
                                    ->defaultValue(5)
                                    ->max(60)
                                ->end()
                                ->booleanNode('verbose_output')
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('watch_dir')
                                    ->defaultValue(self::DEFAULT_WATCH_DIR)
                                ->end()
                            ->end()
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
                                        'mime_types' => [],
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
                                ->variableNode('mime_types')
                                    ->info('File extensions to mime types map.')
                                    ->defaultValue([])
                                    ->validate()
                                        ->always(function ($mimeTypes) {
                                            $validValues = [];

                                            foreach ((array) $mimeTypes as $extension => $mimeType) {
                                                $extension = \trim((string) $extension);
                                                $mimeType = \trim((string) $mimeType);

                                                if ('' === $extension || '' === $mimeType) {
                                                    throw new InvalidTypeException(\sprintf('Invalid mime type %s for file extension %s.', $mimeType, $extension));
                                                }

                                                $validValues[$extension] = $mimeType;
                                            }

                                            return $validValues;
                                        })
                                    ->end()
                                ->end() // end mime types
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
                                    ->values(['json', 'production', 'symfony', 'custom', 'auto'])
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
                                    // TODO: NEXT MAJOR - remove default value
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
                                ->scalarNode('worker_count')
                                    ->defaultValue(1)
                                ->end()
                                ->scalarNode('reactor_count')
                                    ->defaultValue(1)
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
                    ->end()
                ->end() // server
            ->end()
        ;

        return $this->builder;
    }

    public static function fromTreeBuilder(): self
    {
        $treeBuilderClass = TreeBuilder::class;

        return new self(new $treeBuilderClass(self::CONFIG_NAME));
    }
}
