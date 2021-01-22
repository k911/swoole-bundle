<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class StreamedResponseListener implements EventSubscriberInterface
{
    private $contextManager;
    private $responseProcessor;

    public function __construct(
        SwooleRequestResponseContextManager $contextManager,
        ResponseProcessorInterface $responseProcessor
    ) {
        $this->responseProcessor = $responseProcessor;
        $this->contextManager = $contextManager;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();
        if ($response instanceof StreamedResponse) {
            $swooleResponse = $this->contextManager->findResponse($event->getRequest());
            $this->responseProcessor->process($response, $swooleResponse);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
        ];
    }
}
