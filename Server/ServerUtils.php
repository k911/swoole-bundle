<?php

namespace App\Bundle\SwooleBundle\Server;

use Closure;

/**
 * Nitty gritty helper methods to hijack objects. Useful to reset properties that would otherwise run amok
 * and result in memory leaks.
 *
 * @see https://github.com/php-pm/php-pm/blob/master/src/Utils.php
 */
final class ServerUtils
{
    /**
     * Executes a function in the context of an object. This basically bypasses the private/protected check of PHP.
     *
     * @param callable $fn
     * @param object   $newThis
     * @param array    $args
     * @param string   $bindClass
     */
    public static function bindAndCall(callable $fn, object $newThis, array $args = [], $bindClass = null): void
    {
        $func = Closure::bind($fn, $newThis, $bindClass ?: \get_class($newThis));
        if ($args) {
            \call_user_func_array($func, $args);
        } else {
            $func(); //faster
        }
    }

    /**
     * Changes a property value of an object. (hijack because you can also change private/protected properties).
     *
     * @param object $object
     * @param string $propertyName
     * @param mixed  $newValue
     */
    public static function hijackProperty($object, $propertyName, $newValue): void
    {
        $closure = (new ClosureHelper())->getPropertyAccessor($propertyName, $newValue);
        self::bindAndCall($closure, $object);
    }

    /**
     * @return int bytes
     */
    public static function getMaxMemory(): int
    {
        $memoryLimit = \ini_get('memory_limit');
        // if no limit
        if (-1 === $memoryLimit) {
            return 134217728; //128 * 1024 * 1024 default 128mb
        }
        // if set to exact byte
        if (\is_numeric($memoryLimit)) {
            return (int) $memoryLimit;
        }

        // if short hand version http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes
        return \mb_substr($memoryLimit, 0, -1) * [
                'g' => 1073741824, //1024 * 1024 * 1024
                'm' => 1048576, //1024 * 1024
                'k' => 1024,
            ][\mb_strtolower(\mb_substr($memoryLimit, -1))];
    }

    public static function getMemoryUsage(): int
    {
        return \memory_get_usage();
    }

    public static function getPeakMemoryUsage(): int
    {
        return \memory_get_peak_usage();
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 0) {
            return (string) $bytes;
        }

        $label = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $bytes >= 1024 && $i < (\count($label) - 1); $bytes /= 1024, $i++) {
        }

        return \round($bytes, 2).' '.$label[$i];
    }

    public static function decodeStringAsSet(?string $stringSet): array
    {
        if (null === $stringSet) {
            return [];
        }

        $stringSet = \str_replace(['\'', '[', ']'], '', $stringSet);

        return \explode(',', $stringSet);
    }

    private function __construct()
    {
    }
}
