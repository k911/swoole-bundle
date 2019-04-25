<?php
declare(strict_types=1);

/*
 * @author Martin Fris <rasta@lj.sk>
 */

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Service;

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
     * @throws \InvalidArgumentException
     * @throws \Ramsey\Uuid\Exception\UnsatisfiedDependencyException
     * @throws \UnexpectedValueException
     */
    public function process(): array
    {
        $test = new Test($this->uuidFactory->uuid4());
        $this->entityManager->persist($test);
        $this->entityManager->flush();

        $tests = $this->entityManager->getRepository(Test::class)->findBy([], ['id' => 'desc'], 25);

        return $tests;
    }
}
