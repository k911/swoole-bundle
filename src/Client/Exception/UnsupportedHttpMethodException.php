<?php

declare(strict_types=1);

namespace K911\Swoole\Client\Exception;

/**
 * @internal
 */
final class UnsupportedHttpMethodException extends \InvalidArgumentException
{
    /**
     * @param string[] $allowed
     *
     * @return UnsupportedHttpMethodException
     */
    public static function forMethod(string $method, array $allowed): self
    {
        return new self(\sprintf('Http method "%s" is not supported. Only "%s" are supported.', $method, \implode(', ', $allowed)));
    }
}
