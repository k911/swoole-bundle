<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Entity\Test;
use Ramsey\Uuid\UuidFactoryInterface;

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

    public function __construct(EntityManagerInterface $entityManager, UuidFactoryInterface $uuidFactory)
    {
        $this->entityManager = $entityManager;
        $this->uuidFactory = $uuidFactory;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     *
     * @return Test[]
     */
    public function process(): array
    {
        $test = new Test($this->uuidFactory->uuid4());
        $this->entityManager->persist($test);
        $this->entityManager->flush();

        return $this->entityManager->getRepository(Test::class)->findBy([], ['id' => 'desc'], 25);
    }
}
