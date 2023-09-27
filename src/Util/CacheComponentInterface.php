<?php

namespace Mongolid\Util;

/**
 * CacheComponentInteface describes the API of an CacheComponent that may be
 * used with Mongolid.
 */
interface CacheComponentInterface
{
    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key cache key of the item to be retrieved
     */
    public function get(string $key): mixed;

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string $key     cache key of the item
     * @param mixed  $value   value being stored in cache
     * @param float  $minutes cache ttl
     */
    public function put(string $key, mixed $value, float $minutes): void;

    /**
     * Determine if an item exists in the cache. This method will also check
     * if the ttl of the given cache key has been expired and will free the
     * memory if so.
     *
     * @param string $key cache key of the item
     *
     * @return bool has cache key
     */
    public function has(string $key): bool;
}
