<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container;

use Co;

final class CoWrapper
{
    private $servicePoolContainer;

    private static $instance;

    public function __construct(ServicePoolContainer $servicePoolContainer)
    {
        $this->servicePoolContainer = $servicePoolContainer;
        self::$instance = $this;
    }

    public function defer(): void
    {
        Co::defer(function (): void {
            $this->servicePoolContainer->releaseForCoroutine(Co::getCid());
        });
    }

    /**
     * instead of Co::go(), CoWrapper::go() has to be used to run coroutines in Symfony apps, so Symfony
     * is able to reset all stateful service instances.
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public static function go(callable $fn): void
    {
        \go(static function () use ($fn): void {
            self::getInstance()->defer();
            $fn();
        });
    }

    private static function getInstance(): self
    {
        if (null === self::$instance) {
            throw UsageBeforeInitialization::notInitializedYet();
        }

        return self::$instance;
    }
}
