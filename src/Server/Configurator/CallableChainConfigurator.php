<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use Swoole\Http\Server;

final class CallableChainConfigurator implements ConfiguratorInterface
{
    private $configurators;

    /**
     * @param iterable<callable> $configurators
     */
    public function __construct(iterable $configurators)
    {
        $this->configurators = $configurators;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        /** @var callable $configurator */
        foreach ($this->configurators as $configurator) {
            $configurator($server);
        }
    }
}
