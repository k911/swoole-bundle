<?php

declare(strict_types=1);

/*
 * @author Martin Fris <rasta@lj.sk>
 */

namespace K911\Swoole\Common;

/**
 *
 */
final class Decoder
{
    /**
     * Simple decodes string of values as array.
     *
     * @param string|null $stringSet
     * @param string      $separator  set separator
     * @param array       $stripChars characters to be stripped out from string
     *
     * @return string[]
     */
    public static function decodeStringAsSet(?string $stringSet, string $separator = ',', array $stripChars = ['\'', '[', ']']): array
    {
        if (null === $stringSet || '' === $stringSet) {
            return [];
        }

        $stringSet = str_replace($stripChars, '', $stringSet);

        /** @var string[] $set */
        $set = explode($separator, $stringSet);

        return $set;
    }
}
