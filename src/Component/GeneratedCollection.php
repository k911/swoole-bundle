<?php

declare(strict_types=1);

namespace K911\Swoole\Component;

use Generator;
use IteratorAggregate;

/**
 * @template T
 * @implements IteratorAggregate<T>
 */
final class GeneratedCollection implements IteratorAggregate
{
    private $itemCollection;
    private $items;

    /**
     * @param iterable<T> $itemCollection
     * @param T           ...$items
     */
    public function __construct(iterable $itemCollection, ...$items)
    {
        $this->itemCollection = $itemCollection;
        $this->items = $items;
    }

    /**
     * @throws \Exception
     *
     * @return Generator<T>
     */
    public function each(callable $func): Generator
    {
        foreach ($this->getIterator() as $item) {
            yield $func($item);
        }
    }

    /**
     * @throws \Exception
     *
     * @return GeneratedCollection<T>
     */
    public function map(callable $func): self
    {
        return new self($this->each($func));
    }

    /**
     * @throws \Exception
     *
     * @return GeneratedCollection<T>
     */
    public function filter(callable $func): self
    {
        return new self($this->filterItems($func));
    }

    /**
     * {@inheritdoc}
     *
     * @return Generator<T>
     */
    public function getIterator(): Generator
    {
        yield from $this->itemCollection;

        yield from $this->items;
    }

    /**
     * @throws \Exception
     *
     * @return Generator<T>
     */
    private function filterItems(callable $func): Generator
    {
        foreach ($this->getIterator() as $item) {
            if ($func($item)) {
                yield $item;
            }
        }
    }
}
