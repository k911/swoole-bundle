<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Exception;

/**
 * @internal
 */
final class PortUnavailableException extends \InvalidArgumentException
{
    public static function fortPort(int $port): self
    {
        return new self(sprintf('Cannot attach listener on port (%s). This port has already been registered.', $port));
    }
}
