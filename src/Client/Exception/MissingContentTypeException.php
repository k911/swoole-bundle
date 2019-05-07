<?php

declare(strict_types=1);

namespace K911\Swoole\Client\Exception;

/**
 * @internal
 */
final class MissingContentTypeException extends \InvalidArgumentException
{
    public static function make(): self
    {
        return new self(\sprintf('Server response did not contain mandatory header "Content-Type".'));
    }
}
