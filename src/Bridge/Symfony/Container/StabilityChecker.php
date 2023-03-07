<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container;

interface StabilityChecker
{
    public function isStable(object $service): bool;
}
