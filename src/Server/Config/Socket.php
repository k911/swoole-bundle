<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Config;

use Assert\Assertion;
use InvalidArgumentException;

final class Socket
{
    private const CONSTANT_SWOOLE_SSL_IS_NOT_DEFINED_ERROR_MESSAGE = 'Constant SWOOLE_SSL is not defined. Please compile swoole extension with SSL support enabled.';
    private const SWOOLE_SOCKET_TYPE = [
        'tcp' => SWOOLE_SOCK_TCP,
        'tcp_ipv6' => SWOOLE_SOCK_TCP6,
        'udp' => SWOOLE_SOCK_UDP,
        'udp_ipv6' => SWOOLE_SOCK_UDP6,
        'unix_dgram' => SWOOLE_SOCK_UNIX_DGRAM,
        'unix_stream' => SWOOLE_SOCK_UNIX_STREAM,
    ];

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $ssl;

    /**
     * @param string $host
     * @param int    $port
     * @param string $type
     * @param bool   $ssl
     *
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(string $host = 'localhost', int $port = 9501, string $type = 'tcp', bool $ssl = false)
    {
        $this->setHost($host);
        $this->setPort($port);

        if ($ssl) {
            Assertion::defined('SWOOLE_SSL', self::CONSTANT_SWOOLE_SSL_IS_NOT_DEFINED_ERROR_MESSAGE);
        }

        $this->type = $type;
        $this->ssl = $ssl;
    }

    /**
     * @param string $addressPort
     *
     * @return array values:
     *               - string host
     *               - int port
     */
    private static function splitAddressPort(string $addressPort): array
    {
        $pos = mb_strrpos($addressPort, ':');

        if (false !== $pos) {
            $host = mb_substr($addressPort, 0, $pos);
            if ('*' === $host) {
                $host = '0.0.0.0';
            }
            $port = mb_substr($addressPort, $pos + 1);
        } elseif (ctype_digit($addressPort)) {
            $host = '127.0.0.1';
            $port = $addressPort;
        } else {
            $host = $addressPort;
            $port = 9501;
        }

        return [$host, (int) $port];
    }

    /**
     * @param string $addressPort
     * @param string $socketType
     * @param bool   $enableSsl
     *
     * @throws \Assert\AssertionFailedException
     *
     * @return Socket
     */
    public static function fromAddressPort(string $addressPort = '127.0.0.1:9501', string $socketType = 'tcp', bool $enableSsl = false): self
    {
        [$host, $port] = self::splitAddressPort($addressPort);

        return new self($host, $port, $socketType, $enableSsl);
    }

    public function addressPort(): string
    {
        return sprintf('%s:%d', $this->host, $this->port);
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function type(): int
    {
        $resolvedSocketType = self::SWOOLE_SOCKET_TYPE[$this->type];

        if ($this->ssl) {
            if (!\defined('SWOOLE_SSL')) {
                throw new InvalidArgumentException(self::CONSTANT_SWOOLE_SSL_IS_NOT_DEFINED_ERROR_MESSAGE);
            }
            $resolvedSocketType |= SWOOLE_SSL;
        }

        return $resolvedSocketType;
    }

    public function ssl(): bool
    {
        return $this->ssl;
    }

    private function setPort(int $port): void
    {
        Assertion::between($port, 0, 65535, 'Port must be an integer between 0 and 65535');
        $this->port = $port;
    }

    public function withPort(int $port): self
    {
        $self = clone $this;
        $self->setPort($port);
        $self->port = $port;

        return $self;
    }

    public function withHost(string $host): self
    {
        $self = clone $this;
        $self->setHost($host);

        return $self;
    }

    private function setHost(string $host): void
    {
        $this->host = $host;
    }
}
