<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation\Session;

use K911\Swoole\Server\Session\StorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets the session in the request.
 */
final class SetSessionCookieEventListener implements EventSubscriberInterface
{
    private $sessionStorage;
    private $sessionCookieParameters;
    private $swooleStorage;

    public function __construct(SessionStorageInterface $sessionStorage, StorageInterface $swooleStorage, array $sessionOptions = [])
    {
        $this->sessionStorage = $sessionStorage;
        $this->swooleStorage = $swooleStorage;
        $this->sessionCookieParameters = $this->mergeCookieParams($sessionOptions);
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $cookies = $event->getRequest()->cookies;

        $sessionName = $this->sessionStorage->getName();
        if ($cookies->has($sessionName)) {
            $this->sessionStorage->setId($cookies->get($sessionName));
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMasterRequest() || !$this->isSessionRelated($event)) {
            return;
        }

        $session = $event->getRequest()->getSession();
        if (!$session instanceof SessionInterface || !$session->isStarted()) {
            return;
        }

        $responseHeaderBag = $event->getResponse()->headers;
        foreach ($responseHeaderBag->getCookies() as $cookie) {
            if ($this->isSessionCookie($cookie, $session->getName())) {
                return;
            }
        }

        $responseHeaderBag->setCookie($this->makeSessionCookie($session));
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (!$this->isSessionRelated($event)) {
            return;
        }

        if ($this->sessionStorage instanceof SwooleSessionStorage && $this->sessionStorage->isStarted()) {
            $this->sessionStorage->reset();
        }

        $this->swooleStorage->garbageCollect();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 192],
            KernelEvents::RESPONSE => ['onKernelResponse', -128],
            KernelEvents::TERMINATE => ['onKernelTerminate', -128],
        ];
    }

    private function isSessionCookie(Cookie $cookie, string $sessionName): bool
    {
        return $this->sessionCookieParameters['path'] === $cookie->getPath() &&
            $this->sessionCookieParameters['domain'] === $cookie->getDomain() &&
            $sessionName === $cookie->getName();
    }

    private function makeSessionCookie(SessionInterface $session): Cookie
    {
        return new Cookie(
            $session->getName(),
            $session->getId(),
            0 === $this->sessionCookieParameters['lifetime'] ? 0 : \time() + $this->sessionCookieParameters['lifetime'],
            $this->sessionCookieParameters['path'],
            $this->sessionCookieParameters['domain'],
            $this->sessionCookieParameters['secure'],
            $this->sessionCookieParameters['httponly'],
            false,
            $this->sessionCookieParameters['samesite']
        );
    }

    private function mergeCookieParams(array $sessionOptions): array
    {
        $params = \session_get_cookie_params() + ['samesite' => null];
        foreach ($sessionOptions as $k => $v) {
            if (0 === \mb_strpos($k, 'cookie_')) {
                $params[\mb_substr($k, 7)] = $v;
            }
        }

        return $params;
    }

    private function isSessionRelated(KernelEvent $event): bool
    {
        return $event->getRequest()->hasSession();
    }
}
