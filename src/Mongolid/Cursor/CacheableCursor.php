<?php
namespace Mongolid\Cursor;

use ArrayIterator;
use Mongolid\Container\Ioc;
use Mongolid\Serializer\Serializer;
use Mongolid\Util\CacheComponent;
use Traversable;

/**
 * This class wraps the query execution and the actual creation of the driver
 * cursor. But upon it's creation it will already retrieve documents from the
 * database and store the retrieved documents. By doing this, it is possible
 * to serialize the results and save for later use.
 *
 * @package Mongolid
 */
class CacheableCursor extends Cursor
{
    /**
     * The documents that were retrieved from the database in a serializable way
     *
     * @var array
     */
    protected $documents;

    /**
     * Actually returns a Traversable object with the DriverCursor within.
     * If it does not exists yet, create it using the $collection, $command and
     * $params given.
     *
     * The difference between the CacheableCursor and the normal Cursor is that
     * the Cacheable stores all the results within itself and drops the
     * Driver Cursor in order to be serializable.
     *
     * @return Traversable
     */
    protected function getCursor(): Traversable
    {
        if ($this->documents) {
            return $this->documents;
        }

        $cacheComponent = Ioc::make(CacheComponent::class);
        $cacheKey       = $this->generateCacheKey();

        if ($this->documents = $cacheComponent->get($cacheKey, null)) {
            return $this->documents = new ArrayIterator($this->documents);
        }

        // Stores the documents within the object.
        $this->documents = [];
        foreach (parent::getCursor() as $document) {
            $this->documents[] = $document;
        }

        $cacheComponent->put($cacheKey, $this->documents, 0.3);

        // Drops the unserializable DriverCursor. In order to make the
        // CacheableCursor object serializable.
        unset($this->cursor);

        return $this->documents = new ArrayIterator($this->documents);
    }

    /**
     * Generates an unique cache key for the cursor in it's current state.
     *
     * @return string Cache key to identify the query of the current cursor.
     */
    protected function generateCacheKey(): string
    {
        $serializer = Ioc::make(Serializer::class);

        return sprintf(
            '%s:%s:%s',
            $this->command,
            $this->collection->getNamespace(),
            md5($serializer->serialize($this->params))
        );
    }
}
