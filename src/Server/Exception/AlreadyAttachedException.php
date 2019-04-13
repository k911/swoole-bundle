<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Exception;

/**
 * @internal
 */
final class AlreadyAttachedException extends \RuntimeException
{
    public static function create(): self
    {
        return new self('Swoole HTTP Server has been already attached. Cannot attach server or listeners multiple times.');
    }
}
