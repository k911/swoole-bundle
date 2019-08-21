<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Coroutine;

use K911\Swoole\Coroutine\CoroutinePool;
use PHPUnit\Framework\TestCase;

class CoroutinePoolTest extends TestCase
{
    public function testCoroutinePoolWorks(): void
    {
        $value = null;
        $expected = 1;

        $pool = CoroutinePool::fromCoroutines(function () use (&$value, $expected): void {
            $value = $expected;
        });
        $pool->run();

        $this->assertSame($expected, $value);
    }

    public function testCoroutinePoolWithManyCoroutinesWorks(): void
    {
        $value1 = null;
        $expected1 = 1;

        $value2 = null;
        $expected2 = 2;

        $value3 = null;
        $expected3 = 3;

        $pool = CoroutinePool::fromCoroutines(
            function () use (&$value1, $expected1): void {
                $value1 = $expected1;
            },
            function () use (&$value2, $expected2): void {
                $value2 = $expected2;
            },
            function () use (&$value3, $expected3): void {
                $value3 = $expected3;
            }
        );
        $pool->run();

        $this->assertSame($expected1, $value1);
        $this->assertSame($expected2, $value2);
        $this->assertSame($expected3, $value3);
    }

    public function testCoroutinePoolInCoroutinePoolWorks(): void
    {
        $value1 = null;
        $expected1 = 1;

        $value2 = null;
        $expected2 = 2;

        $pool1 = CoroutinePool::fromCoroutines(function () use (&$value1, &$value2, $expected1, $expected2): void {
            $pool2 = CoroutinePool::fromCoroutines(function () use (&$value2, $expected2): void {
                $value2 = $expected2;
            });
            $pool2->run();
            $value1 = $expected1;
        });
        $pool1->run();

        $this->assertSame($expected1, $value1);
        $this->assertSame($expected2, $value2);
    }
}
