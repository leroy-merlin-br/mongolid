<?php

namespace Mongolid\Cursor;

use MongoDB\Collection;
use Mongolid\Schema\Schema;

/**
 * Factory of new EmbeddedCursor instances.
 */
class CursorFactory
{
    /**
     * Creates a new instance of a non embedded Cursor.
     *
     * @param Schema     $entitySchema schema that describes the entity that will be retrieved from the database
     * @param Collection $collection   the raw collection object that will be used to retrieve the documents
     * @param string     $command      the command that is being called in the $collection
     * @param array      $params       the parameters of the $command
     * @param bool       $cacheable    retrieves a CacheableCursor instead
     *
     * @return Cursor
     */
    public function createCursor(
        Schema $entitySchema,
        Collection $collection,
        string $command,
        array $params,
        bool $cacheable = false
    ): Cursor {
        $cursorClass = $cacheable ? CacheableCursor::class : Cursor::class;

        return new $cursorClass($entitySchema, $collection, $command, $params);
    }

    /**
     * Creates a new instance of EmbeddedCursor.
     *
     * @param string $entityClass class of the objects that will be retrieved by the cursor
     * @param array  $items       the items array
     *
     * @return CursorInterface
     */
    public function createEmbeddedCursor(string $entityClass, array $items): CursorInterface
    {
        return new EmbeddedCursor($entityClass, $items);
    }
}
