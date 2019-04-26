<?php

declare(strict_types=1);

namespace K911\Swoole\Client\Exception;

/**
 * @internal
 */
final class ClientConnectionErrorException extends \RuntimeException
{
    public static function failed(int $errorCode): self
    {
        return new self('Connection Failed', $errorCode);
    }

    public static function requestTimeout(int $errorCode): self
    {
        return new self('Request Timeout', $errorCode);
    }

    public static function serverReset(int $errorCode): self
    {
        return new self('Server Reset', $errorCode);
    }

    public static function unknown(int $errorCode): self
    {
        return new self(sprintf('Unknown [%d]', $errorCode), $errorCode);
    }
}
