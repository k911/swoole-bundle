<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Session;

interface StorageInterface
{
    /**
     * Add or update session storage storage by key.
     *
     * @param string $key
     * @param mixed  $data
     * @param int    $ttl  lifetime in seconds
     */
    public function set(string $key, $data, int $ttl): void;

    /**
     * Delete session storage by key.
     *
     * @param string $key
     */
    public function delete(string $key): void;

    /**
     * Invalidate all expired session storage.
     */
    public function garbageCollect(): void;

    /**
     * Get session storage data by key.
     *
     * @param string        $key
     * @param null|callable $expired What to do when key has expired (for example: delete data)
     *                               Signature: function(string $key, $data): void {}
     *
     * @return null|mixed data Should return the same type as provided in set(), null when data is not available or expired
     */
    public function get(string $key, ?callable $expired = null);
}
