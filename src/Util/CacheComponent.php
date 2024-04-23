<?php

declare(strict_types=1);

namespace Mongolid\Util;

/**
 * CacheComponent will cache values for later use based in "key, value"
 * approach.
 */
class CacheComponent implements CacheComponentInterface
{
    /**
     * The array of stored values.
     *
     * @var array<string, mixed>
     */
    protected array $storage = [];

    /**
     * Time to live of each stored value.
     *
     * @var array<string, float>
     */
    protected array $ttl = [];

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key cache key of the item to be retrieved
     */
    public function get(string $key): mixed
    {
        if ($this->has($key)) {
            return $this->storage[$key];
        }

        return null;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string $key     cache key of the item
     * @param mixed  $value   value being stored in cache
     * @param float  $minutes cache ttl
     */
    public function put(string $key, mixed $value, float $minutes): void
    {
        $this->storage[$key] = $value;
        $this->ttl[$key] = $this->time() + 60 * $minutes;
    }

    /**
     * Determine if an item exists in the cache. This method will also check
     * if the ttl of the given cache key has been expired and will free the
     * memory if so.
     *
     * @param string $key cache key of the item
     *
     * @return bool has cache key
     */
    public function has(string $key): bool
    {
        if (
            array_key_exists($key, $this->ttl) &&
            $this->time() - $this->ttl[$key] > 0
        ) {
            unset($this->ttl[$key]);
            unset($this->storage[$key]);

            return false;
        }

        return array_key_exists($key, $this->storage);
    }

    /**
     * Return the current time in order to check ttl.
     *
     * @codeCoverageIgnore
     *
     * @return int return current Unix timestamp
     */
    protected function time(): int
    {
        return time();
    }
}
