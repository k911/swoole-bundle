<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Component\AtomicCounter;

use Swoole\Atomic;

final class AtomicStub extends Atomic
{
    private $value;

    public function __construct(int $value)
    {
        parent::__construct(0);
        $this->value = $value;
    }

    public function get(): int
    {
        return $this->value;
    }
}
