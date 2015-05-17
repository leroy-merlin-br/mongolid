<?php
namespace Mongolid;

use MongoId;

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
     * that are not specified in the $schema property. This is usefull if you
     * doesn't have a strict document format or if you want to take full
     * advantage of the "schemaless" nature of MongoDB.
     * @var boolean
     */
    protected $dynamic = false;

    /**
     * Tells how a document should look like. If an scalar type is used, it will
     * perform a cast into the value. Othewise the schema will use the type as
     * the name of the method to be called. See 'mongoId' method for example.
     * @var string[]
     */
    protected $schema  = [
        '_id' => 'mongoId' // Means that the _id will passtrought the `mongoId` method
    ];

    /**
     * Filters any field in the $schema that has it's value specified as a
     * 'mongoId'. It will wraps the $value, if any, into a MongoId object
     *
     * @param  mixed $value
     *
     * @return MongoId
     */
    public function mongoId($value = null)
    {
        if (! $value instanceof MongoId) {
            $value = new MongoId($value);
        }

        return $value;
    }
}
