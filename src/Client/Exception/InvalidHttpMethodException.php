<?php

declare(strict_types=1);

namespace K911\Swoole\Client\Exception;

/**
 * @internal
 */
final class InvalidHttpMethodException extends \InvalidArgumentException
{
    public static function forMethod(string $method, array $allowed): self
    {
        return new self(\sprintf('Content-Type "%s" is not supported. Only "%s" are supported.', $method, \implode(', ', $allowed)));
    }
}
