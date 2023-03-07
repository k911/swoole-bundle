<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container;

use Co;
use Symfony\Component\DependencyInjection\Container;

final class ServicePool
{
    /**
     * @var string
     */
    private $wrappedServiceId;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var null|StabilityChecker
     */
    private $stabilityChecker;

    /**
     * @var array<int, object>
     */
    private array $freePool = [];

    /**
     * @var array<int, object>
     */
    private array $assignedPool = [];

    public function __construct(
        string $wrappedServiceId,
        Container $container,
        ?StabilityChecker $stabilityChecker = null
    ) {
        $this->wrappedServiceId = $wrappedServiceId;
        $this->container = $container;
        $this->stabilityChecker = $stabilityChecker;
    }

    public function get(): object
    {
        $cId = $this->getCoroutineId();

        if (isset($this->assignedPool[$cId])) {
            return $this->assignedPool[$cId];
        }

        if (!empty($this->freePool)) {
            return $this->assignedPool[$cId] = \array_shift($this->freePool);
        }

        return $this->assignedPool[$cId] = $this->container->get($this->wrappedServiceId);
    }

    public function releaseForCoroutine(int $cId): void
    {
        if (!isset($this->assignedPool[$cId])) {
            return;
        }

        $service = $this->assignedPool[$cId];
        unset($this->assignedPool[$cId]);

        if (!$this->isServiceStable($service)) {
            return;
        }

        $this->freePool[] = $service;
    }

    private function getCoroutineId(): int
    {
        return Co::getCid();
    }

    private function isServiceStable(object $service): bool
    {
        return null === $this->stabilityChecker || $this->stabilityChecker->isStable($service);
    }
}
