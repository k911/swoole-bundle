<?php

declare(strict_types=1);

/*
 * @author Martin Fris <rasta@lj.sk>
 */

namespace K911\Swoole\Bridge\Symfony\RequestCycle;

/**
 *
 */
interface InitializerInterface
{
    /**
     *
     */
    public function initialize(): void;
}
