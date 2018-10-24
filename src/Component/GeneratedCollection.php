<?php

declare(strict_types=1);

namespace K911\Swoole\Component;

use Generator;
use IteratorAggregate;

final class GeneratedCollection implements IteratorAggregate
{
    private $itemCollection;
    private $items;

    /**
     * @param iterable<mixed> $itemCollection
     * @param mixed           ...$items
     */
    public function __construct(iterable $itemCollection, ...$items)
    {
        $this->itemCollection = $itemCollection;
        $this->items = $items;
    }

    public function each(callable $func): Generator
    {
        foreach ($this->getIterator() as $item) {
            yield $func($item);
        }
    }

    public function map(callable $func): self
    {
        return new self($this->each($func));
    }

    private function filterItems(callable $func): Generator
    {
        foreach ($this->getIterator() as $item) {
            if ($func($item)) {
                yield $item;
            }
        }
    }

    public function filter(callable $func): self
    {
        return new self($this->filterItems($func));
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Generator
    {
        foreach ($this->itemCollection as $handler) {
            yield $handler;
        }

        foreach ($this->items as $handler) {
            yield $handler;
        }
    }
}
