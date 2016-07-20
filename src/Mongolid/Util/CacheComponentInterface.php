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
     * @param  string $key Cache key of the item to be retrieved.
     *
     * @return mixed
     */
    public function get(string $key);

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string $key     Cache key of the item.
     * @param mixed  $value   Value being stored in cache.
     * @param float  $minutes Cache ttl.
     *
     * @return void
     */
    public function put(string $key, $value, float $minutes);

    /**
     * Determine if an item exists in the cache. This method will also check
     * if the ttl of the given cache key has been expired and will free the
     * memory if so.
     *
     * @param  string $key Cache key of the item.
     *
     * @return bool Has cache key.
     */
    public function has(string $key): bool;
}
