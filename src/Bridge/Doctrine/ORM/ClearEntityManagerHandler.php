<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Doctrine\ORM;

use Doctrine\Persistence\ManagerRegistry;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class ClearEntityManagerHandler implements RequestHandlerInterface
{
    private $decorated;
    private $managerRegistry;

    public function __construct(RequestHandlerInterface $decorated, ManagerRegistry $managerRegistry)
    {
        $this->decorated = $decorated;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, Response $response): void
    {
        $this->decorated->handle($request, $response);

        foreach ($this->managerRegistry->getManagers() as $manager) {
            $manager->clear();
        }
    }
}
