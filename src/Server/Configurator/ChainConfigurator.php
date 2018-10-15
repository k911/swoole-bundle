<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use Assert\Assertion;
use Generator;
use IteratorAggregate;
use Swoole\Http\Server;

final class ChainConfigurator implements ConfiguratorInterface, IteratorAggregate
{
    /**
     * @var iterable<ConfiguratorInterface>
     */
    private $collection;

    /**
     * @var ConfiguratorInterface[]
     */
    private $configurators;

    /**
     * @param iterable<ConfiguratorInterface> $configuratorCollection
     * @param ConfiguratorInterface           ...$configurators
     */
    public function __construct(iterable $configuratorCollection, ConfiguratorInterface ...$configurators)
    {
        $this->configurators = $configurators;
        $this->collection = $configuratorCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        /** @var ConfiguratorInterface $configurator */
        foreach ($this->getIterator() as $configurator) {
            $configurator->configure($server);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Generator
    {
        foreach ($this->collection as $item) {
            Assertion::isInstanceOf($item, ConfiguratorInterface::class);
            yield $item;
        }

        foreach ($this->configurators as $configurator) {
            yield $configurator;
        }
    }
}
