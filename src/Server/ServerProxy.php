<?php

declare(strict_types=1);

namespace K911\Swoole\Server;

/**
 * Proxy object to allow using symfony bundle without `symfony/proxy-manager-bridge` package.
 */
final class ServerProxy implements ServerInterface
{
    /**
     * @var ServerFactoryInterface
     */
    private $factory;

    /**
     * @var ServerInterface
     */
    private $instance;

    public function __construct(ServerFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function start(): bool
    {
        return $this->obtainInstance()->start();
    }

    public function shutdown(): void
    {
        $this->obtainInstance()->shutdown();
    }

    public function reload(): void
    {
        $this->obtainInstance()->reload();
    }

    public function metrics(): array
    {
        return $this->obtainInstance()->metrics();
    }

    public function dispatchTask($data): void
    {
        $this->obtainInstance()->dispatchTask($data);
    }

    public function running(): bool
    {
        return $this->obtainInstance()->running();
    }

    public function info(): array
    {
        return $this->obtainInstance()->info();
    }

    private function obtainInstance(): ServerInterface
    {
        if (!$this->instance instanceof ServerInterface) {
            $this->instance = $this->factory->make();
        }

        return $this->instance;
    }
}
