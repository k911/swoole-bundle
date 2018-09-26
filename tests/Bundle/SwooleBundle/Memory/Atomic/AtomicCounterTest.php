<?php

declare(strict_types=1);

namespace App\Tests\Bundle\SwooleBundle\Memory\Atomic;

use App\Bundle\SwooleBundle\Component\AtomicCounter;
use Generator;
use PHPUnit\Framework\TestCase;

class AtomicCounterTest extends TestCase
{
    public function testCreateCounter(): void
    {
        $counter = AtomicCounter::fromZero();

        $this->assertSame(0, $counter->get());
    }

    public function countProvider(): Generator
    {
        return $this->generateArrays(0, 999, 65563);
    }

    /**
     * @dataProvider countProvider
     *
     * @param int $count
     */
    public function testSingleThreadedCounting(int $count): void
    {
        $counter = AtomicCounter::fromZero();

        $this->incrementCounter($counter, $count);

        $this->assertSame($count, $counter->get());
    }

    private function generateArrays(...$values): Generator
    {
        foreach ($values as $value) {
            yield [$value];
        }
    }

    private function incrementCounter(AtomicCounter $counter, int $times): void
    {
        while ($times > 0) {
            $counter->increment();
            --$times;
        }
    }
}
