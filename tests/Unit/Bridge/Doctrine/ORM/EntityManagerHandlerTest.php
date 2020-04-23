<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Bridge\Doctrine\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use K911\Swoole\Bridge\Doctrine\ORM\EntityManagerHandler;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Swoole\Http\Request;
use Swoole\Http\Response;

class EntityManagerHandlerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @var EntityManagerHandler
     */
    private $httpDriver;

    /**
     * @var ObjectProphecy|RequestHandlerInterface
     */
    private $decoratedProphecy;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface|ObjectProphecy
     */
    private $entityManagerProphecy;

    /**
     * @var Connection|ObjectProphecy
     */
    private $connectionProphecy;

    protected function setUp(): void
    {
        $this->entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $this->decoratedProphecy = $this->prophesize(RequestHandlerInterface::class);
        $this->connectionProphecy = $this->prophesize(Connection::class);

        /** @var RequestHandlerInterface $decoratedMock */
        $decoratedMock = $this->decoratedProphecy->reveal();

        /** @var EntityManagerInterface $emMock */
        $emMock = $this->entityManagerProphecy->reveal();

        $this->setUpEntityManagerConnection();
        $this->httpDriver = new EntityManagerHandler($decoratedMock, $emMock);
    }

    public function testHandleNoReconnect(): void
    {
        $this->connectionProphecy->ping()->willReturn(true)->shouldBeCalled();

        $request = new Request();
        $response = new Response();
        $this->decoratedProphecy->handle($request, $response)->shouldBeCalled();

        $this->entityManagerProphecy->clear()->shouldBeCalled();

        $this->httpDriver->handle($request, $response);
    }

    public function testHandleWithReconnect(): void
    {
        $this->connectionProphecy->ping()->willReturn(false)->shouldBeCalled();
        $this->connectionProphecy->close()->shouldBeCalled();
        $this->connectionProphecy->connect()->willReturn(true)->shouldBeCalled();

        $request = new Request();
        $response = new Response();
        $this->decoratedProphecy->handle($request, $response)->shouldBeCalled();

        $this->entityManagerProphecy->clear()->shouldBeCalled();

        $this->httpDriver->handle($request, $response);
    }

    private function setUpEntityManagerConnection(): void
    {
        $this->entityManagerProphecy->getConnection()->willReturn($this->connectionProphecy->reveal());
    }
}
