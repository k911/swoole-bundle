<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use K911\Swoole\Bridge\Symfony\Container\Resetter;

final class EntityManagerResetter implements Resetter
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function reset(): void
    {
        $this->entityManager->clear();
    }
}
