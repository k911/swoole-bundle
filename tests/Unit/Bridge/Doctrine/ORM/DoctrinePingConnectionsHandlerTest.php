<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Bridge\Doctrine\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Persistence\ManagerRegistry;
use K911\Swoole\Bridge\Doctrine\ORM\DoctrinePingConnectionsHandler;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Swoole\Http\Request;
use Swoole\Http\Response;

class DoctrinePingConnectionsHandlerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    private const DUMMY_SQL = 'SELECT 1';
    /**
     * @var RequestHandlerInterface
     */
    private $httpDriver;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var Connection|ObjectProphecy
     */
    private $connectionProphecy;

    protected function setUp(): void
    {
        $this->registry = $this->prophesize(ManagerRegistry::class);
        $this->connectionProphecy = $this->prophesize(Connection::class);
        $this->connectionProphecy->close()->will(function (): void {
            $this->isConnected()->willReturn(false);
        });
        $this->connectionProphecy->connect()->will(function (): void {
            $this->isConnected()->willReturn(true);
        });
        $dbPlatform = $this->prophesize(AbstractPlatform::class);
        $dbPlatform->getDummySelectSQL()->willReturn(static::DUMMY_SQL);
        $this->connectionProphecy->getDatabasePlatform()->willReturn($dbPlatform->reveal());
        $this->registry->getConnections()->shouldBeCalled()->willReturn([$this->connectionProphecy->reveal()]);

        $decoratedProphecy = $this->prophesize(RequestHandlerInterface::class);
        $decoratedProphecy
            ->handle(Argument::type(Request::class), Argument::type(Response::class))
            ->shouldBeCalledOnce()
        ;

        /** @var RequestHandlerInterface $decoratedMock */
        $decoratedMock = $decoratedProphecy->reveal();
        /** @var ManagerRegistry $registry */
        $registry = $this->registry->reveal();

        $this->httpDriver = new DoctrinePingConnectionsHandler($decoratedMock, $registry);
    }

    public function testPingSuccessful(): void
    {
        $this
            ->connectionProphecy
            ->executeQuery(Argument::exact(static::DUMMY_SQL))
            ->willReturn($this->prophesize(ResultStatement::class)->reveal())
            ->shouldBeCalledOnce();

        $request = new Request();
        $response = new Response();

        $this->httpDriver->handle($request, $response);
    }

    public function testPingFails(): void
    {
        if (\class_exists(Exception::class)) {
            $exceptionClass = Exception::class;
        } else {
            $exceptionClass = DBALException::class;
        }

        $this
            ->connectionProphecy
            ->executeQuery(Argument::exact(static::DUMMY_SQL))
            ->willThrow($exceptionClass);

        $request = new Request();
        $response = new Response();

        $this->httpDriver->handle($request, $response);

        self::assertTrue($this->connectionProphecy->reveal()->isConnected());
    }
}
