<?php
namespace Mongolid\Model;

use Mongolid\Schema\AbstractSchema;

interface ModelInterface extends HasAttributesInterface
{
    /**
     * Returns a Schema object that describes an Model in MongoDB.
     */
    public function getSchema(): AbstractSchema;

    /**
     * Parses an object to a document.
     *
     * @param mixed $model the object to be parsed
     */
    public function parseToDocument($model): array;
}
