<?php
namespace Mongolid\Model;

use MongoDB\BSON\Persistable;
use Mongolid\Schema\DynamicSchema;

interface ModelInterface extends HasAttributesInterface, Persistable
{
    /**
     * Returns a Schema object that describes an Model in MongoDB.
     */
    public function getSchema(): DynamicSchema;
}
