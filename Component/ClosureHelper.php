<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Component;

/**
 * Helper class to avoid creating closures in static context.
 *
 * @see https://bugs.php.net/bug.php?id=64761
 * @see https://github.com/php-pm/php-pm/blob/master/src/ClosureHelper.php
 */
final class ClosureHelper
{
    /**
     * Return a closure that assigns a property value.
     *
     * @param string $propertyName
     * @param mixed  $newValue
     *
     * @return callable
     */
    public function getPropertyAccessor(string $propertyName, $newValue): callable
    {
        return function () use ($propertyName, $newValue): void {
            $this->$propertyName = $newValue;
        };
    }
}
