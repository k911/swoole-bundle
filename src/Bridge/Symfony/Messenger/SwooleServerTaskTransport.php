<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

final class SwooleServerTaskTransport implements TransportInterface
{
    private $receiver;
    private $sender;

    public function __construct(SwooleServerTaskReceiver $receiver, SwooleServerTaskSender $sender)
    {
        $this->receiver = $receiver;
        $this->sender = $sender;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Envelope $envelope): Envelope
    {
        return $this->sender->send($envelope);
    }

    /**
     * {@inheritdoc}
     */
    public function get(): iterable
    {
        return $this->receiver->get();
    }

    /**
     * {@inheritdoc}
     */
    public function ack(Envelope $envelope): void
    {
        $this->receiver->ack($envelope);
    }

    /**
     * {@inheritdoc}
     */
    public function reject(Envelope $envelope): void
    {
        $this->receiver->reject($envelope);
    }
}
