<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Config;

use Assert\Assertion;
use InvalidArgumentException;

final class Socket
{
    private const HOST_PORT_DELIMITER = ':';
    private const CONSTANT_SWOOLE_SSL_IS_NOT_DEFINED_ERROR_MESSAGE = 'Constant SWOOLE_SSL is not defined. Please compile swoole extension with SSL support enabled.';
    private const SWOOLE_SOCKET_TYPE = [
        'tcp' => SWOOLE_SOCK_TCP,
        'tcp_ipv6' => SWOOLE_SOCK_TCP6,
        'tcp_dualstack' => SWOOLE_SOCK_TCP | SWOOLE_SOCK_TCP6,
        'udp' => SWOOLE_SOCK_UDP,
        'udp_ipv6' => SWOOLE_SOCK_UDP6,
        'udp_dualstack' => SWOOLE_SOCK_UDP | SWOOLE_SOCK_UDP6,
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
    private $encryption;

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(string $host = '0.0.0.0', int $port = 9501, string $type = 'tcp_dualstack', bool $encryption = false)
    {
        $this->setHost($host);
        $this->setPort($port);
        $this->setType($type);
        $this->setEncryption($encryption);

        // TODO: if tcp/udp with ipv6 only verify whether host is either domain name or IPv6
        // TODO: if tcp/udp with ipv4 only verify whether host is either domain name or IPv4
        // TODO: if tcp/udp with dualstack verify whether host is either domain name or IPv4/IPv6
        // TODO: socket verify something probably
    }

    /**
     * @throws \Assert\AssertionFailedException
     *
     * @return Socket
     */
    public static function fromAddressPort(string $addressPort = 'localhost:9501', string $socketType = 'tcp_dualstack', bool $secure = false): self
    {
        [$host, $port] = self::splitAddressPort($addressPort);

        return new self($host, $port, $socketType, $secure);
    }

    public function addressPort(): string
    {
        return \sprintf('%s:%d', $this->host, $this->port);
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function hostPort(): string
    {
        return $this->host.self::HOST_PORT_DELIMITER.$this->port;
    }

    public function type(): int
    {
        $resolvedSocketType = self::SWOOLE_SOCKET_TYPE[$this->type];

        if ($this->encryption && \defined('SWOOLE_SSL')) {
            $resolvedSocketType |= SWOOLE_SSL;
        }

        return $resolvedSocketType;
    }

    public function secure(): bool
    {
        return $this->encryption;
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

    public function withEncryption(bool $encryption): self
    {
        $self = clone $this;
        $self->setEncryption($encryption);

        return $self;
    }

    public function withType(string $type): self
    {
        $self = clone $this;
        $self->setType($type);

        return $self;
    }

    /**
     * @return array values:
     *               - string host
     *               - int port
     */
    private static function splitAddressPort(string $addressPort): array
    {
        $pos = \mb_strrpos($addressPort, self::HOST_PORT_DELIMITER);

        if (false !== $pos) {
            $host = \mb_substr($addressPort, 0, $pos);
            if ('*' === $host) {
                $host = '0.0.0.0';
            }
            $port = \mb_substr($addressPort, $pos + 1);
        } elseif (\ctype_digit($addressPort)) {
            $host = '127.0.0.1';
            $port = $addressPort;
        } else {
            $host = $addressPort;
            $port = 9501;
        }

        return [$host, (int) $port];
    }

    private function setPort(int $port): void
    {
        Assertion::between($port, 0, 65535, 'Port must be an integer between 0 and 65535');
        $this->port = $port;
    }

    private function setHost(string $host): void
    {
        $this->host = $host;
    }

    private function setEncryption(bool $encryption): void
    {
        if ($encryption && !\defined('SWOOLE_SSL')) {
            throw new InvalidArgumentException(self::CONSTANT_SWOOLE_SSL_IS_NOT_DEFINED_ERROR_MESSAGE);
        }

        $this->encryption = $encryption;
    }

    private function setType(string $type): void
    {
        Assertion::keyExists(self::SWOOLE_SOCKET_TYPE, $type, 'Unknown socket type "%s"');
        $this->type = $type;
    }
}
