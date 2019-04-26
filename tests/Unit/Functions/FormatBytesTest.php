<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Functions;

use K911\Swoole\Common\Formatter;
use PHPUnit\Framework\TestCase;

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
        $this->assertSame($formatted, Formatter::formatBytes($bytes));
    }

    public function testNegativeBytes(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $this->expectExceptionMessage('Bytes number cannot be negative');
        Formatter::formatBytes(-1);
    }
}
