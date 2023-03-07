<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test")
 */
class Test
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var UuidInterface
     * @ORM\Column(type="guid")
     */
    private $uuid;

    public function __construct(UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @throws \Ramsey\Uuid\Exception\InvalidUuidStringException
     *
     * @return UuidInterface
     */
    public function getUuid()
    {
        return $this->uuid;
    }
}
