<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Bridge\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use K911\Swoole\Bridge\Doctrine\ORM\ClearEntityManagerHandler;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Swoole\Http\Request;
use Swoole\Http\Response;

class ClearEntityManagerHandlerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @var ClearEntityManagerHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $registryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerProphecy = $this->prophesize(EntityManagerInterface::class);
        $registryProphecy->getManagers()->willReturn([$managerProphecy->reveal()]);

        $decoratedProphecy = $this->prophesize(RequestHandlerInterface::class);
        $decoratedProphecy
            ->handle(Argument::type(Request::class), Argument::type(Response::class))
            ->shouldBeCalledOnce()
            ->will(function () use ($managerProphecy): void {
                $managerProphecy->clear()->shouldBeCalledOnce();
            })
        ;

        /** @var RequestHandlerInterface $decoratedMock */
        $decoratedMock = $decoratedProphecy->reveal();
        /** @var ManagerRegistry $registry */
        $registry = $registryProphecy->reveal();

        $this->handler = new ClearEntityManagerHandler($decoratedMock, $registry);
    }

    public function testIsCleared(): void
    {
        $request = new Request();
        $response = new Response();

        $this->handler->handle($request, $response);
    }
}
