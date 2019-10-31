<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation\Session;

use Assert\Assertion;
use K911\Swoole\Server\Session\Exception\LogicException;
use K911\Swoole\Server\Session\Exception\RuntimeException;
use K911\Swoole\Server\Session\StorageInterface;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

final class SwooleSessionStorage implements SessionStorageInterface
{
    public const DEFAULT_SESSION_NAME = 'SWOOLESSID';

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $currentId;

    /**
     * @var SessionBagInterface[]
     */
    private $bags;

    /**
     * @var array
     */
    private $data;

    /**
     * @var MetadataBag
     */
    private $metadataBag;

    /**
     * @var bool
     */
    private $started;

    /**
     * @var int
     */
    private $sessionLifetimeSeconds;

    public function __construct(StorageInterface $storage, string $name = self::DEFAULT_SESSION_NAME, int $lifetimeSeconds = 86400, MetadataBag $metadataBag = null)
    {
        $this->storage = $storage;
        $this->name = $name;
        $this->setLifetimeSeconds($lifetimeSeconds);
        $this->setMetadataBag($metadataBag);

        $this->currentId = '';
        $this->started = false;
        $this->data = [];
        $this->bags = [];
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Assert\AssertionFailedException
     * @throws \Exception
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        if (empty($this->currentId)) {
            $this->currentId = $this->generateId();
        }

        $this->data = $this->obtainSessionData($this->currentId);
        $this->bindBagsToData($this->data);

        $this->started = true;

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function regenerate($destroy = false, $lifetime = null): bool
    {
        if ($destroy) {
            $this->storage->delete($this->currentId);
        }

        $this->getMetadataBag()->stampNew($lifetime ?? $this->sessionLifetimeSeconds);
        $this->currentId = $this->generateId();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function save(): void
    {
        if (!$this->started) {
            throw new RuntimeException('Trying to save a session that was not started yet or was already closed');
        }

        $this->storage->set(
            $this->currentId,
            \json_encode($this->data, \JSON_THROW_ON_ERROR),
            $this->sessionLifetimeSeconds
        );
    }

    public function reset(): void
    {
        foreach ($this->allBags() as $bag) {
            $bag->clear();
        }

        $this->started = false;
        $this->currentId = '';
        $this->data = [];
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->storage->delete($this->currentId);
        $this->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->isStarted() ? $this->currentId : '';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function setId($id): void
    {
        if ($this->started) {
            throw new LogicException('Cannot set session ID after the session has started.');
        }

        $this->currentId = \preg_match('/^[a-f0-9]{63}$/', $id) ? $id : $this->generateId();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Assert\AssertionFailedException
     */
    public function getBag($name): SessionBagInterface
    {
        if (!isset($this->bags[$name])) {
            throw new \InvalidArgumentException(\sprintf('The SessionBagInterface `%s` is not registered.', $name));
        }

        if (!$this->started) {
            $this->start();
        }

        return $this->bags[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function registerBag(SessionBagInterface $bag): void
    {
        if ($this->started) {
            throw new \LogicException('Cannot register a bag when the session is already started.');
        }

        $this->bags[$bag->getName()] = $bag;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataBag(): MetadataBag
    {
        return $this->metadataBag;
    }

    private function setLifetimeSeconds(int $lifetimeSeconds): void
    {
        $this->sessionLifetimeSeconds = $lifetimeSeconds;
        \ini_set('session.cookie_lifetime', (string) $lifetimeSeconds);
    }

    /**
     * @param string $sessionId
     *
     * @throws \Assert\AssertionFailedException
     *
     * @return array
     */
    private function obtainSessionData(string $sessionId): array
    {
        $sessionData = $this->storage->get($sessionId, function (): void {
            $this->regenerate(true);
        });

        if (null === $sessionData) {
            return [];
        }

        Assertion::string($sessionData);

        return \json_decode($sessionData, true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * @return iterable&SessionBagInterface[]
     */
    private function allBags(): iterable
    {
        yield from $this->bags;
        yield $this->metadataBag;
    }

    private function bindBagsToData(array &$data): void
    {
        foreach ($this->allBags() as $bag) {
            $key = $bag->getStorageKey();
            $data[$key] = $data[$key] ?? [];
            $bag->initialize($data[$key]);
        }
    }

    /**
     * Generates a session ID.
     *
     * @throws \Exception
     *
     * @return string
     */
    private function generateId(): string
    {
        return \mb_substr(\bin2hex(\random_bytes(32)), \random_int(0, 1), 63);
    }

    private function setMetadataBag(?MetadataBag $metadataBag = null): void
    {
        if (!$metadataBag instanceof MetadataBag) {
            $metadataBag = new MetadataBag();
        }

        $this->metadataBag = $metadataBag;
    }
}
