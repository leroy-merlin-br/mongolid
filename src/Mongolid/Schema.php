<?php
namespace Mongolid;

use MongoDB\BSON\ObjectID;

/**
 * A schema maps to a MongoDB collection and defines the shape of the documents
 * within that collection.
 *
 * @package  Mongolid
 */
abstract class Schema
{
    /**
     * The $dynamic property tells if the schema will accept additional fields
     * that are not specified in the $fields property. This is usefull if you
     * doesn't have a strict document format or if you want to take full
     * advantage of the "schemaless" nature of MongoDB.
     * @var boolean
     */
    public $dynamic = false;

    /**
     * Name of the collection where this kind of document is going to be saved
     * or retrieved from
     * @var string
     */
    public $collection = 'mongolid';

    /**
     * Tells how a document should look like. If an scalar type is used, it will
     * perform a cast into the value. Othewise the schema will use the type as
     * the name of the method to be called. See 'objectId' method for example.
     * @var string[]
     */
    public $fields  = [
        '_id' => 'objectId' // Means that the _id will passtrought the `objectId` method
    ];

    /**
     * Name of the class that will be used to represent a document of this
     * Schema when retrieve from the database.
     * @var string
     */
    public $entityClass = 'stdClass';

    /**
     * Filters any field in the $fields that has it's value specified as a
     * 'objectId'. It will wraps the $value, if any, into a ObjectID object
     *
     * @param  mixed $value
     *
     * @return ObjectID
     */
    public function objectId($value = null)
    {
        if (! $value instanceof ObjectID) {
            $value = new ObjectID($value);
        }

        return $value;
    }
}
