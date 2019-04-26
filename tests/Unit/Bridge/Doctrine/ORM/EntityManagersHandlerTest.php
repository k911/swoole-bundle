<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Bridge\Doctrine\ORM;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use K911\Swoole\Bridge\Doctrine\ORM\EntityManagersHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 *
 */
class EntityManagersHandlerTest extends TestCase
{
    /**
     * @var EntityManagersHandler
     */
    private $emHandler;

    /**
     * @var Registry|ObjectProphecy
     */
    private $doctrineRegistryProphecy;

    /**
     * @var EntityManagerInterface|ObjectProphecy
     */
    private $entityManagerProphecy;

    /**
     * @var Connection|ObjectProphecy
     */
    private $connectionProphecy;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $this->doctrineRegistryProphecy = $this->prophesize(Registry::class);
        $this->connectionProphecy = $this->prophesize(Connection::class);

        /** @var Registry $doctrineRegistryMock */
        $doctrineRegistryMock = $this->doctrineRegistryProphecy->reveal();

        $this->setUpRegistryEnityManagers();
        $this->setUpEntityManagerConnection();
        $this->emHandler = new EntityManagersHandler($doctrineRegistryMock);
    }

    /**
     *
     */
    public function testHandleNoReconnectOnAppInit(): void
    {
        $this->connectionProphecy->ping()->willReturn(true)->shouldBeCalled();
        $this->emHandler->initialize();
    }

    /**
     *
     */
    public function testHandleEntityManagerClearingOnAppTermination(): void
    {
        $this->entityManagerProphecy->clear()->shouldBeCalled();
        $this->emHandler->terminate();
    }

    /**
     *
     */
    public function testHandleWithReconnectOnAppInit(): void
    {
        $this->connectionProphecy->ping()->willReturn(false)->shouldBeCalled();
        $this->connectionProphecy->close()->shouldBeCalled();
        $this->connectionProphecy->connect()->willReturn(true)->shouldBeCalled();

        $this->emHandler->initialize();
    }

    /**
     *
     */
    private function setUpRegistryEnityManagers(): void
    {
        $this->doctrineRegistryProphecy->getManagers()->willReturn([$this->entityManagerProphecy->reveal()]);
    }

    /**
     *
     */
    private function setUpEntityManagerConnection(): void
    {
        $this->entityManagerProphecy->getConnection()->willReturn($this->connectionProphecy->reveal());
    }
}
