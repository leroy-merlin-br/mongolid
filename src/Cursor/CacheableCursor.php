<?php

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
     * The documents that were retrieved from the database in a serializable way.
     *
     * @var array
     */
    protected $documents;

    /**
     * Limit of the query. It is stored because when caching the documents
     * the DOCUMENT_LIMIT const will be used.
     *
     * @var int
     */
    protected $originalLimit;

    /**
     * Means that the CacheableCursor is wapping the original cursor and not
     * reading from Cache anymore.
     *
     * @var bool
     */
    protected $ignoreCache = false;

    /**
     * Limits the amount of documents that will be cached for performance reasons.
     */
    const DOCUMENT_LIMIT = 100;

    /**
     * Serializes this object. Drops the unserializable DriverCursor. In order
     * to make the CacheableCursor object serializable.
     *
     * @return string serialized object
     */
    public function __serialize(): array
    {
        $this->documents = $this->cursor = null;

        return parent::__serialize();
    }

    /**
     * Actually returns a Traversable object with the DriverCursor within.
     * If it does not exists yet, create it using the $collection, $command and
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
        } catch (ErrorException $error) {
            $cachedDocuments = [];
        }

        if ($cachedDocuments) {
            return $this->documents = new ArrayIterator($cachedDocuments);
        }

        // Stores the original "limit" clause of the query
        $this->storeOriginalLimit();

        // Stores the documents within the object and cache then for later use
        $this->documents = [];
        foreach (parent::getCursor() as $document) {
            $this->documents[] = $document;
        }

        $cacheComponent->put($cacheKey, $this->documents, 36);

        // Drops the unserializable DriverCursor.
        $this->cursor = null;

        // Return the documents iterator
        return $this->documents = new ArrayIterator($this->documents);
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
    protected function storeOriginalLimit()
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
     *
     * @return mixed Int or null
     */
    protected function getLimit()
    {
        return $this->originalLimit ?: ($this->params[1]['limit'] ?? null);
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
}
