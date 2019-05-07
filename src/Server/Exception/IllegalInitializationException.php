<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Exception;

/**
 * @internal
 */
final class IllegalInitializationException extends \RuntimeException
{
    public static function make(): self
    {
        return new self('Swoole HTTP Server has been already initialized. Cannot attach server or listeners multiple times.');
    }
}
