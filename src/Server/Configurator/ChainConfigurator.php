<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use Swoole\Http\Server;

final class ChainConfigurator implements ConfiguratorInterface
{
    /**
     * @var iterable<ConfiguratorInterface>
     */
    private $configurators;

    /**
     * @param iterable<ConfiguratorInterface> $configurators
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
        /** @var ConfiguratorInterface $configurator */
        foreach ($this->configurators as $configurator) {
            $configurator->configure($server);
        }
    }
}
