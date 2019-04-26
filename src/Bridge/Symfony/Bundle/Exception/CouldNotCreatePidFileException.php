<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Exception;

/**
 * @internal
 */
final class CouldNotCreatePidFileException extends \RuntimeException
{
    public static function forPath(string $pidFile): self
    {
        throw new self(sprintf('Could not create pid file "%s".', $pidFile));
    }
}
