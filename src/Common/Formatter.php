<?php

declare(strict_types=1);

/*
 * @author Martin Fris <rasta@lj.sk>
 */

namespace K911\Swoole\Common;

use OutOfRangeException;

/**
 *
 */
final class Formatter
{
    /**
     * @param int $bytes
     *
     * @return string
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 0) {
            throw new OutOfRangeException('Bytes number cannot be negative.');
        }

        $labels = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
        $labelsCount = count($labels) - 1;
        $i = 0;
        while ($bytes >= 1024 && $i < $labelsCount) {
            ++$i;
            $bytes /= 1024;
        }

        return sprintf('%s %s', (string) round($bytes, 2), $labels[$i]);
    }
}
