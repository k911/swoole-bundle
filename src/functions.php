<?php

declare(strict_types=1);

namespace K911\Swoole;

use OutOfRangeException;

/**
 * Replaces object property with provided value.
 * Property may not be public.
 *
 * @param object $obj
 * @param string $propertyName
 * @param mixed  $newValue
 */
function replace_object_property(object $obj, string $propertyName, $newValue): void
{
    (function (string $propertyName, $newValue): void {
        $this->$propertyName = $newValue;
    })->call($obj, $propertyName, $newValue);
}

/**
 * Get object property.
 * Property may not be public.
 *
 * @param object $obj
 * @param string $propertyName
 *
 * @return mixed
 */
function get_object_property(object $obj, string $propertyName)
{
    return (function (string $propertyName) {
        return $this->$propertyName;
    })->call($obj, $propertyName);
}

/**
 * @return int bytes
 */
function get_max_memory(): int
{
    /** @var string $memoryLimit */
    $memoryLimit = \ini_get('memory_limit');

    // if no limit
    if ('-1' === $memoryLimit) {
        return 134217728; //128 * 1024 * 1024 default 128mb
    }
    // if set to exact byte
    if (\is_numeric($memoryLimit)) {
        return (int) $memoryLimit;
    }

    // if short hand version http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes
    $shortHandMemoryLimit = (int) \mb_substr($memoryLimit, 0, -1);

    return $shortHandMemoryLimit * [
            'g' => 1073741824, //1024 * 1024 * 1024
            'm' => 1048576, //1024 * 1024
            'k' => 1024,
        ][\mb_strtolower(\mb_substr($memoryLimit, -1))];
}

function format_bytes(int $bytes): string
{
    if ($bytes < 0) {
        throw new OutOfRangeException('Bytes number cannot be negative.');
    }

    $labels = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
    $labelsCount = \count($labels) - 1;
    $i = 0;
    while ($bytes >= 1024 && $i < $labelsCount) {
        ++$i;
        $bytes /= 1024;
    }

    return \sprintf('%s %s', (string) \round($bytes, 2), $labels[$i]);
}

/**
 * Simple decodes string of values as array.
 *
 * @param null|string $stringSet
 * @param string      $separator  set separator
 * @param array       $stripChars characters to be stripped out from string
 *
 * @return string[]
 */
function decode_string_as_set(?string $stringSet, string $separator = ',', array $stripChars = ['\'', '[', ']']): array
{
    if (null === $stringSet || '' === $stringSet) {
        return [];
    }

    $stringSet = \str_replace($stripChars, '', $stringSet);

    /** @var string[] $set */
    $set = \explode($separator, $stringSet);

    return $set;
}
