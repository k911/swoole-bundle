<?php

declare(strict_types=1);

namespace K911\Swoole\Client\Exception;

/**
 * @internal
 */
final class UnsupportedContentTypeException extends \InvalidArgumentException
{
    /**
     * @param string   $contentType
     * @param string[] $allowed
     *
     * @return UnsupportedContentTypeException
     */
    public static function forContentType(string $contentType, array $allowed): self
    {
        return new self(\sprintf('Content-Type "%s" is not supported. Only "%s" are supported.', $contentType, \implode(', ', $allowed)));
    }
}
