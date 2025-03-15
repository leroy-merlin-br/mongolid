<?php

declare(strict_types=1);

namespace Mongolid\Cursor;

use Iterator;
use ArrayIterator;
use ErrorException;
use Mongolid\Container\Container;
use Mongolid\Util\CacheComponentInterface;

/**
 * This class wraps the query execution and the actual creation of the driver
 * cursor. But upon it's creation it will already retrieve documents from the
 * database and store the retrieved documents. By doing this, it is possible
 * to serialize the results and save for later use.
 */
class CacheableCursor extends Cursor
{
    /**
     * Limits the amount of documents that will be cached for performance reasons.
     */
    public const DOCUMENT_LIMIT = 100;

    /**
     * The documents that were retrieved from the database in a serializable way.
     */
    protected ?Iterator $documents = null;

    /**
     * Limit of the query. It is stored because when caching the documents
     * the DOCUMENT_LIMIT const will be used.
     */
    protected int $originalLimit = 0;

    /**
     * Means that the CacheableCursor is wrapping the original cursor and not
     * reading from Cache anymore.
     */
    protected bool $ignoreCache = false;

    /**
     * Actually returns a Traversable object with the DriverCursor within.
     * If it does not exist yet, create it using the $collection, $command and
     * $params given.
     *
     * The difference between the CacheableCursor and the normal Cursor is that
     * the Cacheable stores all the results within itself and drops the
     * Driver Cursor in order to be serializable.
     */
    protected function getCursor(): Iterator
    {
        // Returns original (non-cached) cursor
        if ($this->ignoreCache || $this->position >= self::DOCUMENT_LIMIT) {
            return $this->getOriginalCursor();
        }

        // Returns cached set of documents
        if ($this->documents) {
            return $this->documents;
        }

        // Check if there is a cached set of documents
        $cacheComponent = Container::make(CacheComponentInterface::class);
        $cacheKey = $this->generateCacheKey();

        try {
            $cachedDocuments = $cacheComponent->get($cacheKey, null);
        } catch (ErrorException) {
            $cachedDocuments = [];
        }

        if ($cachedDocuments) {
            return $this->documents = new ArrayIterator($cachedDocuments);
        }

        // Stores the original "limit" clause of the query
        $this->storeOriginalLimit();

        // Stores the documents within the object and cache then for later use
        $documents = [];
        foreach (parent::getCursor() as $document) {
            $documents[] = $document;
        }

        $cacheComponent->put($cacheKey, $documents, 36);

        // Drops the unserializable DriverCursor.
        $this->cursor = null;

        // Return the documents iterator
        return $this->documents = new ArrayIterator($documents);
    }

    /**
     * Generates an unique cache key for the cursor in it's current state.
     *
     * @return string cache key to identify the query of the current cursor
     */
    protected function generateCacheKey(): string
    {
        return sprintf(
            '%s:%s:%s',
            $this->command,
            $this->collection->getNamespace(),
            md5(serialize($this->params))
        );
    }

    /**
     * Stores the original "limit" clause of the query.
     */
    protected function storeOriginalLimit(): void
    {
        if (isset($this->params[1]['limit'])) {
            $this->originalLimit = $this->params[1]['limit'];
        }

        if ($this->originalLimit > self::DOCUMENT_LIMIT) {
            $this->limit(self::DOCUMENT_LIMIT);
        }
    }

    /**
     * Gets the limit clause of the query if any.
     */
    protected function getLimit(): int
    {
        return $this->originalLimit ?: ($this->params[1]['limit'] ?? 0);
    }

    /**
     * Returns the DriverCursor considering the documents that have already
     * been retrieved from cache.
     */
    protected function getOriginalCursor(): Iterator
    {
        if ($this->ignoreCache) {
            return parent::getCursor();
        }

        if ($this->getLimit()) {
            $this->params[1]['limit'] = $this->getLimit() - $this->position;
        }

        $skipped = $this->params[1]['skip'] ?? 0;

        $this->skip($skipped + $this->position - 1);

        $this->ignoreCache = true;

        return $this->getOriginalCursor();
    }

    /**
     * Serializes this object. Drops the unserializable DriverCursor. In order
     * to make the CacheableCursor object serializable.
     */
    public function __serialize(): array
    {
        $this->documents = $this->cursor = null;

        return parent::__serialize();
    }
}
