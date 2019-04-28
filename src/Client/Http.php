<?php

declare(strict_types=1);

namespace K911\Swoole\Client;

final class Http
{
    public const METHOD_GET = 'GET';
    public const METHOD_HEAD = 'HEAD';
    public const METHOD_POST = 'POST';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_TRACE = 'TRACE';
    public const METHOD_OPTIONS = 'OPTIONS';

    public const HEADER_CONTENT_TYPE = 'content-type';
    public const HEADER_ACCEPT = 'accept';

    public const CONTENT_TYPE_APPLICATION_JSON = 'application/json';
    public const CONTENT_TYPE_TEXT_PLAIN = 'text/plain';
    public const CONTENT_TYPE_TEXT_HTML = 'text/html';

    private function __construct()
    {
    }
}
