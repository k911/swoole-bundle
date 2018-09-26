<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Functions;

use PHPUnit\Framework\TestCase;
use function K911\Swoole\format_bytes;

class FormatBytesTest extends TestCase
{
    public function bytesFormattedProvider(): array
    {
        return [
            '0 bytes' => [
                0,
                '0 B',
            ],
            '100 bytes' => [
                100,
                '100 B',
            ],
            '1024 bytes' => [
                1024,
                '1 KiB',
            ],
            '2024 bytes' => [
                2024,
                '1.98 KiB',
            ],
            '20240 bytes' => [
                20240,
                '19.77 KiB',
            ],
            '2*2^30 bytes' => [
                2 * 2 ** 30,
                '2 GiB',
            ],
            'PHP_INT_MAX bytes' => [
                PHP_INT_MAX,
                '8192 PiB',
            ],
        ];
    }

    /**
     * @dataProvider bytesFormattedProvider
     *
     * @param int    $bytes
     * @param string $formatted
     */
    public function testFormatBytes(int $bytes, string $formatted): void
    {
        $this->assertSame($formatted, format_bytes($bytes));
    }

    /**
     * @expectedException \OutOfRangeException
     * @expectedExceptionMessage Bytes number cannot be negative
     */
    public function testNegativeBytes(): void
    {
        format_bytes(-1);
    }
}
