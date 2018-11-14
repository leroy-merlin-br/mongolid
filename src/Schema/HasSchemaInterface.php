<?php
namespace Mongolid\Schema;

interface HasSchemaInterface
{
    /**
     * Returns a Schema object that describes an Entity in MongoDB.
     */
    public function getSchema(): AbstractSchema;

    /**
     * Parses an object to a document.
     *
     * @param mixed $entity the object to be parsed
     */
    public function parseToDocument($entity): array;
}
