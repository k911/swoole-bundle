<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Bridge\Symfony\Messenger;

use K911\Swoole\Bridge\Symfony\Messenger\Exception\ReceiverNotAvailableException;
use K911\Swoole\Bridge\Symfony\Messenger\SwooleServerTaskReceiver;
use K911\Swoole\Bridge\Symfony\Messenger\SwooleServerTaskSender;
use K911\Swoole\Bridge\Symfony\Messenger\SwooleServerTaskTransport;
use K911\Swoole\Server\Config\Socket;
use K911\Swoole\Server\Config\Sockets;
use K911\Swoole\Server\HttpServer;
use K911\Swoole\Server\HttpServerConfiguration;
use PHPStan\Testing\TestCase;
use Symfony\Component\Messenger\Envelope;

class SwooleServerTaskTransportTest extends TestCase
{
    public function testThatItThrowsExceptionOnAck(): void
    {
        $transport = new SwooleServerTaskTransport(new SwooleServerTaskReceiver(), new SwooleServerTaskSender($this->makeHttpServerDummy()));

        $this->expectException(ReceiverNotAvailableException::class);

        $transport->ack(new Envelope($this->prophesize('object')));
    }

    public function testThatItThrowsExceptionOnReject(): void
    {
        $transport = new SwooleServerTaskTransport(new SwooleServerTaskReceiver(), new SwooleServerTaskSender($this->makeHttpServerDummy()));

        $this->expectException(ReceiverNotAvailableException::class);

        $transport->reject(new Envelope($this->prophesize('object')));
    }

    private function makeHttpServerDummy(): HttpServer
    {
        return new HttpServer(new HttpServerConfiguration(new Sockets(new Socket())));
    }
}
