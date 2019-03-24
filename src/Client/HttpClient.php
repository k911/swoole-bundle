<?php

declare(strict_types=1);

namespace K911\Swoole\Client;

use Assert\Assertion;
use Swoole\Coroutine\Http\Client;

/**
 * Mainly used for server tests.
 *
 * @internal Class API is not stable, nor it is guaranteed to exists in next releases, use at own risk
 */
final class HttpClient
{
    private const SUPPORTED_HTTP_METHODS = [
        'GET',
        'HEAD',
        'POST',
        'DELETE',
        'PATCH',
        'TRACE',
        'OPTIONS',
    ];

    private const CONTENT_TYPE_APPLICATION_JSON = 'application/json';
    private const CONTENT_TYPE_TEXT_PLAIN = 'text/plain';
    private const SUPPORTED_CONTENT_TYPES = [
        self::CONTENT_TYPE_APPLICATION_JSON,
        self::CONTENT_TYPE_TEXT_PLAIN,
    ];

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param int $timeout seconds
     * @param int $step    microseconds
     *
     * @return bool Success
     */
    public function connect(int $timeout = 3, int $step = 500): bool
    {
        $timeoutMicro = \microtime(true) + $timeout;

        do {
            try {
                $this->send('/', 'HEAD');

                return true;
            } catch (\RuntimeException $ex) {
                if (111 !== $ex->getCode()) {
                    throw $ex;
                }
            }
            \usleep($step);
        } while (\microtime(true) < $timeoutMicro);

        return false;
    }

    public function send(string $path, string $method = 'GET', array $headers = [], $data = null, int $timeout = 3): array
    {
        Assertion::inArray($method, self::SUPPORTED_HTTP_METHODS, 'Method "%s" is not supported. Supported ones are: %s.');

        $this->client->setMethod($method);
        $this->client->setHeaders($headers);
        $this->client->execute($path);

        if (null !== $data) {
            if (\is_string($data)) {
                $this->client->setData($data);
            } else {
                $this->serializeRequestData($this->client, $data);
            }
        }

        return $this->resolveResponse($this->client, $timeout);
    }

    public static function fromDomain(string $host, int $port = 443, bool $ssl = true, array $options = []): self
    {
        $client = new Client(
            $host, $port, $ssl
        );

        if (!empty($options)) {
            $client->set($options);
        }

        return new self($client);
    }

    public function __destruct()
    {
        if ($this->client->connected) {
            $this->client->close();
        }
    }

    private function serializeRequestData(Client $client, $data): void
    {
        $options = \defined('JSON_THROW_ON_ERROR') ? \JSON_THROW_ON_ERROR : 0;
        $json = \json_encode($data, $options);

        // TODO: Drop on PHP 7.3 Migration
        if (!\defined('JSON_THROW_ON_ERROR') && false === $json) {
            throw new \RuntimeException(\json_last_error_msg(), \json_last_error());
        }

        $client->headers['content-type'] = 'application/json';
        $client->setData($json);
    }

    /**
     * @param Client $client
     *
     * @return string|array
     */
    private function resolveResponseBody(Client $client)
    {
        if (204 === $client->statusCode || '' === $client->body) {
            return [];
        }

        Assertion::keyExists($client->headers, 'content-type', 'Server response did not contain Content-Type.');
        $fullContentType = $client->headers['content-type'];
        $contentType = \explode(';', $fullContentType)[0];

        switch ($contentType) {
            case self::CONTENT_TYPE_APPLICATION_JSON:
                // TODO: Drop on PHP 7.3 Migration
                if (!\defined('JSON_THROW_ON_ERROR')) {
                    $data = \json_decode($client->body, true);
                    if (null === $data) {
                        throw new \RuntimeException(\json_last_error_msg(), \json_last_error());
                    }

                    return $data;
                }

                return \json_decode($client->body, true, 512, JSON_THROW_ON_ERROR);
            case self::CONTENT_TYPE_TEXT_PLAIN:
                return $client->body;
            default:
                throw new \RuntimeException(\sprintf('Content-Type "%s" is not supported. Only "%s" are supported.', $contentType, \implode(', ', self::SUPPORTED_CONTENT_TYPES)));
        }
    }

    private function resolveResponse(Client $client, int $timeout): array
    {
        $client->recv($timeout);

        if ($client->statusCode < 0) {
            switch ($client->statusCode) {
                case -1:
                    $error = 'Connection Failed';
                    break;
                case -2:
                    $error = 'Request Timeout';
                    break;
                case -3:
                    $error = 'Server Reset';
                    break;
                default:
                    $error = 'Unknown';
                    break;
            }

            throw new \RuntimeException($error, $client->errCode);
        }

        return [
            'request' => [
                'method' => $client->requestMethod,
                'headers' => $client->requestHeaders,
                'body' => $client->requestBody,
                'cookies' => $client->set_cookie_headers,
                'uploadFiles' => $client->uploadFiles,
            ],
            'response' => [
                'cookies' => $client->cookies,
                'headers' => $client->headers,
                'statusCode' => $client->statusCode,
                'body' => $this->resolveResponseBody($client),
                'downloadFile' => $client->downloadFile,
                'downloadOffset' => $client->downloadOffset,
            ],
        ];
    }
}
