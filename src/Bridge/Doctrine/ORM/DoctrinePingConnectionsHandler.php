<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Doctrine\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class DoctrinePingConnectionsHandler implements RequestHandlerInterface
{
    private $decorated;
    private $registry;

    public function __construct(RequestHandlerInterface $decorated, ManagerRegistry $registry)
    {
        $this->decorated = $decorated;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, Response $response): void
    {
        foreach ($this->registry->getConnections() as $connection) {
            if (!$connection instanceof Connection) {
                continue;
            }

            $this->pingConnection($connection);
        }

        $this->decorated->handle($request, $response);
    }

    private function pingConnection(Connection $connection): void
    {
        try {
            $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL());
        } catch (DBALException | Exception $e) {
            $connection->close();
            $connection->connect();
        }
    }
}
