<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container;

use Symfony\Contracts\Service\ResetInterface;

final class ServicePoolContainer implements ResetInterface
{
    /**
     * @var array<ServicePool>
     */
    private $pools;

    /**
     * @var array<Resetter>
     */
    private $resetters;

    /**
     * @param array<ServicePool> $pools
     * @param array<Resetter>    $resetters
     */
    public function __construct(array $pools, array $resetters)
    {
        $this->pools = $pools;
        $this->resetters = $resetters;
    }

    public function releaseForCoroutine(int $cId): void
    {
        foreach ($this->pools as $pool) {
            $pool->releaseForCoroutine($cId);
        }
    }

    public function reset(): void
    {
        foreach ($this->resetters as $resetter) {
            $resetter->reset();
        }
    }
}
