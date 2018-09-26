<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Runtime;

use Assert\Assertion;

final class BootManager implements BootableInterface
{
    private $booted;
    private $services;

    public function __construct(bool $booted = false, BootableInterface ...$services)
    {
        $this->booted = $booted;
        foreach ($services as $service) {
            $this->addService($service);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Assert\AssertionFailedException When already booted
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        Assertion::false($this->booted, 'Boot method has already been called. Cannot boot services multiple times.');
        $this->booted = true;

        $booted = [];

        /**
         * @var int
         * @var BootableInterface $service
         */
        foreach ($this->services as $id => $service) {
            if (!isset($booted[$id])) {
                $service->boot($runtimeConfiguration);
                $booted[$id] = true;
            }
        }
    }

    public function addService(BootableInterface $service): void
    {
        $this->services[\spl_object_id($service)] = $service;
    }
}
