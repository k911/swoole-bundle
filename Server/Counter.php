<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Server;

use OutOfRangeException;

final class Counter
{
    private $counter = 0;

    /**
     * @throws \OutOfRangeException
     */
    public function increment(): void
    {
        if (PHP_INT_MAX === $this->counter) {
            throw new OutOfRangeException('Exceeded maximum value for integer');
        }

        ++$this->counter;
    }

    public function get(): int
    {
        return $this->counter;
    }

    public function reset(): void
    {
        $this->counter = 0;
    }
}
