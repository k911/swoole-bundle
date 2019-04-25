<?php
declare(strict_types=1);

/*
 * @author Martin Fris <rasta@lj.sk>
 */

namespace K911\Swoole\Bridge\Symfony\RequestCycle;

/**
 *
 */
final class Initializers
{
    /**
     * @var InitializerInterface[]|iterable
     */
    private $initializers;

    /**
     * @param iterable|InitializerInterface[] $initializers
     */
    public function __construct($initializers)
    {
        $this->initializers = $initializers;
    }

    /**
     *
     */
    public function initialize(): void
    {
        foreach ($this->initializers as $initializer) {
            $initializer->initialize();
        }
    }
}
