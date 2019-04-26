<?php

declare(strict_types=1);

/*
 * @author Martin Fris <rasta@lj.sk>
 */

namespace K911\Swoole\Common;

use Closure;

/**
 *
 */
final class Reflection
{
    /**
     * Replaces object property with provided value.
     * Property may not be public.
     *
     * @param object      $obj
     * @param string      $propertyName
     * @param mixed       $newValue
     * @param string|null $scope        class scope useful when property is inherited
     */
    public static function replaceObjectProperty(object $obj, string $propertyName, $newValue, ?string $scope = null): void
    {
        Closure::bind(function (string $propertyName, $newValue): void {
            $this->$propertyName = $newValue;
        }, $obj, $scope ?? $obj)($propertyName, $newValue);
    }

    /**
     * Get object property (even by reference).
     * Property may not be public.
     *
     * @param object      $obj
     * @param string      $propertyName
     * @param string|null $scope        class scope useful when property is inherited
     *
     * @return mixed
     */
    public static function &getObjectProperty(object $obj, string $propertyName, ?string $scope = null)
    {
        return Closure::bind(function &(string $propertyName) {
            return $this->$propertyName;
        }, $obj, $scope ?? $obj)($propertyName);
    }
}
