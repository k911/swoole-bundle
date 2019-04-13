<?php

declare(strict_types=1);

namespace K911\Swoole\Client\Exception;

use K911\Swoole\Client\Http;

/**
 * @internal
 */
final class InvalidContentTypeException extends \InvalidArgumentException
{
    private const SUPPORTED_CONTENT_TYPES = [
        Http::CONTENT_TYPE_APPLICATION_JSON,
        Http::CONTENT_TYPE_TEXT_PLAIN,
    ];

    public static function forContentType(string $contentType): self
    {
        return new self(\sprintf('Content-Type "%s" is not supported. Only "%s" are supported.', $contentType, \implode(', ', self::SUPPORTED_CONTENT_TYPES)));
    }
}
