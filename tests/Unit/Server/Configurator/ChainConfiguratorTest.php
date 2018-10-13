<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\Configurator;

use K911\Swoole\Server\Configurator\ChainConfigurator;
use K911\Swoole\Tests\Unit\Server\SwooleHttpServerDummy;
use PHPUnit\Framework\TestCase;

class ChainConfiguratorTest extends TestCase
{
    public function testConfigureConfigurators(): void
    {
        $configuratorSpies = [new ConfiguratorSpy(), new ConfiguratorSpy(), new ConfiguratorSpy()];
        $serverDummy = new SwooleHttpServerDummy();

        $chain = new ChainConfigurator($configuratorSpies);

        $chain->configure($serverDummy);

        /** @var ConfiguratorSpy $configuratorSpy */
        foreach ($configuratorSpies as $configuratorSpy) {
            $this->assertTrue($configuratorSpy->configured);
        }
    }
}
