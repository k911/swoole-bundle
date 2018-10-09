<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Component\AtomicCounter;

use K911\Swoole\Component\AtomicCounter;
use PHPUnit\Framework\TestCase;

class AtomicCounterTest extends TestCase
{
    public function testConstructFromZero(): void
    {
        $counter = AtomicCounter::fromZero();
        $this->assertSame(0, $counter->get());
    }

    public function testIncrement(): void
    {
        $atomicSpy = new AtomicSpy();
        $this->assertFalse($atomicSpy->incremented);

        $counter = new AtomicCounter($atomicSpy);
        $counter->increment();

        $this->assertTrue($atomicSpy->incremented);
    }

    public function testGet(): void
    {
        $count = 10;
        $counter = new AtomicCounter(new AtomicStub($count));

        $this->assertSame($count, $counter->get());
    }
}
