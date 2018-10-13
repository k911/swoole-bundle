<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Runtime;

use Assert\Assertion;

/**
 * Chain of services implementing BootableInterface.
 */
final class BootManager implements BootableInterface
{
    private $booted;
    private $services;

    /**
     * @param iterable<BootableInterface> $services
     * @param bool                        $booted
     */
    public function __construct(iterable $services, bool $booted = false)
    {
        $this->services = $services;
        $this->booted = $booted;
    }

    /**
     * {@inheritdoc}
     *
     * Method MUST be called directly before Swoole server start.
     *
     * @throws \Assert\AssertionFailedException When already booted
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        Assertion::false($this->booted, 'Boot method has already been called. Cannot boot services multiple times.');
        $this->booted = true;

        $booted = [];

        /** @var BootableInterface $service */
        foreach ($this->services as $service) {
            $id = \spl_object_id($service);
            if (!isset($booted[$id])) {
                $service->boot($runtimeConfiguration);
                $booted[$id] = true;
            }
        }
    }
}
