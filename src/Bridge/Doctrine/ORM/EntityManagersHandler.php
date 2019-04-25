<?php
declare(strict_types=1);

/*
 * @author Martin Fris <rasta@lj.sk>
 */

namespace K911\Swoole\Bridge\Doctrine\ORM;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use K911\Swoole\Bridge\Symfony\RequestCycle\InitializerInterface;
use K911\Swoole\Bridge\Symfony\RequestCycle\TerminatorInterface;

/**
 *
 */
final class EntityManagersHandler implements InitializerInterface, TerminatorInterface
{
    /**
     * @var Connection[]
     */
    private $connections;

    /**
     * @var EntityManager[]
     */
    private $entityManagers;

    /**
     * @param Registry $doctrineRegistry
     */
    public function __construct(Registry $doctrineRegistry)
    {
        $this->entityManagers = $doctrineRegistry->getManagers();
        $this->connections = array_map(static function (EntityManager $entityManager) {
            return $entityManager->getConnection();
        }, $this->entityManagers);
    }

    /**
     *
     */
    public function initialize(): void
    {
        foreach ($this->connections as $connection) {
            if ($connection->ping()) {
                continue;
            }

            $connection->close();
            $connection->connect();
        }
    }

    /**
     *
     * @throws MappingException
     */
    public function terminate(): void
    {
        foreach ($this->entityManagers as $entityManager) {
            $entityManager->clear();
        }
    }
}
