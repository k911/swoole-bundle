<?php
declare(strict_types=1);

/*
 * @author Martin Fris <rasta@lj.sk>
 */

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Service;

use Exception;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Entity\Test;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidFactoryInterface;

/**
 *
 */
final class DummyService
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UuidFactoryInterface
     */
    private $uuidFactory;

    /**
     * @param EntityManagerInterface $entityManager
     * @param UuidFactoryInterface   $uuidFactory
     */
    public function __construct(EntityManagerInterface $entityManager, UuidFactoryInterface $uuidFactory)
    {
        $this->entityManager = $entityManager;
        $this->uuidFactory = $uuidFactory;
    }

    /**
     * @return Test[]
     * @throws Exception
     */
    public function process(): array
    {
        for ($i = 0; $i < 10; $i++) {
            $this->newEntity();
        }

        $tests = $this->entityManager->getRepository(Test::class)->findBy([], ['id' => 'desc'], 25);

        return $tests;
    }

    /**
     *
     */
    private function newEntity(): void
    {
        $test = new Test($this->uuidFactory->uuid4());
        $this->entityManager->persist($test);
        $this->entityManager->flush();
    }
}
