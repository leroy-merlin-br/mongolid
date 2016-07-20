<?php

namespace Mongolid\Cursor;

use MongoDB\Collection;
use Mongolid\Schema;

/**
 * Factory of new EmbeddedCursor instances.
 */
class CursorFactory
{
    /**
     * Creates a new instance of a non embedded Cursor.
     *
     * @param Schema     $entitySchema Schema that describes the entity that will be retrieved from the database.
     * @param Collection $collection   The raw collection object that will be used to retrieve the documents.
     * @param string     $command      The command that is being called in the $collection.
     * @param array      $params       The parameters of the $command.
     * @param bool       $cacheable    Retrieves a CacheableCursor instead.
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
     * @param string $entityClass Class of the objects that will be retrieved by the cursor.
     * @param array  $items       The items array.
     *
     * @return CursorInterface
     */
    public function createEmbeddedCursor(string $entityClass, array $items): CursorInterface
    {
        return new EmbeddedCursor($entityClass, $items);
    }
}
