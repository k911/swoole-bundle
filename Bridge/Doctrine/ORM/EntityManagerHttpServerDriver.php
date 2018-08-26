<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Doctrine\ORM;

use App\Bundle\SwooleBundle\Server\HttpServerDriverInterface;
use Doctrine\ORM\EntityManagerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class EntityManagerHttpServerDriver implements HttpServerDriverInterface
{
    private $decorated;
    private $connection;
    private $entityManager;

    public function __construct(HttpServerDriverInterface $decorated, EntityManagerInterface $entityManager)
    {
        $this->decorated = $decorated;
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        $this->decorated->boot($runtimeConfiguration);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, Response $response): void
    {
        if (!$this->connection->ping()) {
            $this->connection->close();
            $this->connection->connect();
        }

        $this->decorated->handle($request, $response);

        $this->entityManager->clear();
    }
}
