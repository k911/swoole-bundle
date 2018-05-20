<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Server;

use Swoole\Atomic;

final class Counter
{
    private $counter;

    private function __construct(Atomic $counter)
    {
        $this->counter = $counter;
    }

    public function increment(): void
    {
        $this->counter->add(1);
    }

    public function get(): int
    {
        return $this->counter->get();
    }

    public function reset(): void
    {
        $this->counter->set(0);
    }

    public static function fromZero(): self
    {
        return new self(new Atomic(0));
    }
}
