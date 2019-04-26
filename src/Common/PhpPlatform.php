<?php

declare(strict_types=1);

/*
 * @author Martin Fris <rasta@lj.sk>
 */

namespace K911\Swoole\Common;

/**
 *
 */
final class PhpPlatform
{
    /**
     * @return int bytes
     */
    public static function getMaxMemory(): int
    {
        /** @var string $memoryLimit */
        $memoryLimit = ini_get('memory_limit');

        // if no limit
        if ('-1' === $memoryLimit) {
            return 134217728; //128 * 1024 * 1024 default 128mb
        }
        // if set to exact byte
        if (is_numeric($memoryLimit)) {
            return (int) $memoryLimit;
        }

        // if short hand version http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes
        $shortHandMemoryLimit = (int) mb_substr($memoryLimit, 0, -1);

        return $shortHandMemoryLimit * [
                'g' => 1073741824, //1024 * 1024 * 1024
                'm' => 1048576, //1024 * 1024
                'k' => 1024,
            ][mb_strtolower(mb_substr($memoryLimit, -1))];
    }
}
