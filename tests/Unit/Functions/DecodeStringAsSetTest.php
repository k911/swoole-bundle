<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Functions;

use K911\Swoole\Common\Decoder;
use PHPUnit\Framework\TestCase;

class DecodeStringAsSetTest extends TestCase
{
    public function decodedPairsProvider(): array
    {
        return [
            'normal set' => [
                'value1,value2,value3',
                ['value1', 'value2', 'value3'],
            ],
            'json set' => [
                "['value1','value2','value3']",
                ['value1', 'value2', 'value3'],
            ],
            'empty apostrophe set' => [
                "['''',''''',''''']",
                ['', '', ''],
            ],
            'apostrophe set' => [
                "['value1''','''value2'','''value3'']",
                ['value1', 'value2', 'value3'],
            ],
            'set from empty string' => [
                '',
                [],
            ],
            'empty set from null' => [
                null,
                [],
            ],
        ];
    }

    /**
     * @dataProvider decodedPairsProvider
     *
     * @param string $string
     * @param array  $set
     */
    public function testDecodeStringAsSet(?string $string, array $set): void
    {
        $this->assertSame($set, Decoder::decodeStringAsSet($string));
    }
}
