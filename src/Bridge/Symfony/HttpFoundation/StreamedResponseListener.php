<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\StreamedResponseListener as HttpFoundationStreamedResponseListener;
use Symfony\Component\HttpKernel\KernelEvents;

class StreamedResponseListener implements EventSubscriberInterface
{
    private $delegate;

    public function __construct(?HttpFoundationStreamedResponseListener $delegate = null)
    {
        $this->delegate = $delegate;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $attributes = $event->getRequest()->attributes;

        if ($response instanceof StreamedResponse &&
            $attributes->has(ResponseProcessorInjectorInterface::ATTR_KEY_RESPONSE_PROCESSOR)
        ) {
            $processor = $attributes->get(ResponseProcessorInjectorInterface::ATTR_KEY_RESPONSE_PROCESSOR);
            $processor($response);

            return;
        }

        if (null === $this->delegate) {
            return;
        }

        $this->delegate->onKernelResponse($event);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
        ];
    }
}
