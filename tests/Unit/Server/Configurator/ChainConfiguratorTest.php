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

        $manager = new ChainConfigurator($configuratorSpies);

        $manager->configure($serverDummy);

        /** @var ConfiguratorSpy $configuratorSpy */
        foreach ($configuratorSpies as $configuratorSpy) {
            $this->assertTrue($configuratorSpy->configured);
        }
    }

    public function testConfigureConfiguratorsUsingVariadic(): void
    {
        $configuratorSpies = [new ConfiguratorSpy(), new ConfiguratorSpy(), new ConfiguratorSpy()];
        $serverDummy = new SwooleHttpServerDummy();

        $manager = new ChainConfigurator([], ...$configuratorSpies);

        $manager->configure($serverDummy);

        /** @var ConfiguratorSpy $configuratorSpy */
        foreach ($configuratorSpies as $configuratorSpy) {
            $this->assertTrue($configuratorSpy->configured);
        }
    }

    public function testConstructWithNotConfiguratorsWithoutConfigureShouldNotThrow(): void
    {
        new ChainConfigurator([$this->prophesize('object')]);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @expectedException \Assert\InvalidArgumentException
     * @expectedExceptionMessageRegExp  /Class "[a-zA-Z\\]+" was expected to be instanceof of "K911\\Swoole\\Server\\Configurator\\ConfiguratorInterface" but is not/
     */
    public function testConstructWithNotConfiguratorsWithConfigureShouldThrow(): void
    {
        $serverDummy = new SwooleHttpServerDummy();

        $chain = new ChainConfigurator([$this->prophesize('object')]);

        $chain->configure($serverDummy);

        $this->expectNotToPerformAssertions();
    }

    public function testIterator(): void
    {
        $configuratorDummiesOne = [new ConfiguratorDummy(), new ConfiguratorDummy(), new ConfiguratorDummy()];
        $configuratorDummiesTwo = [new ConfiguratorDummy(), new ConfiguratorDummy()];

        $chain = new ChainConfigurator($configuratorDummiesOne, ...$configuratorDummiesTwo);

        $this->assertSame(\array_merge($configuratorDummiesOne, $configuratorDummiesTwo), \iterator_to_array($chain));
    }
}
