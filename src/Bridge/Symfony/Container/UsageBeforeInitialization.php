<?php

declare(strict_types=1);

/*
 * @author    mfris
 * @copyright PIXELFEDERATION s.r.o.
 * @license   Internal use only
 */

namespace K911\Swoole\Bridge\Symfony\Container;

use RuntimeException;
use Throwable;

final class UsageBeforeInitialization extends RuntimeException
{
    private function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function notInitializedYet(): self
    {
        return new self('CoWrapper was not initialised yet.');
    }
}
