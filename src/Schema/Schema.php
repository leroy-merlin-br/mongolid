<?php

namespace Mongolid\Schema;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Container\Container;
use Mongolid\Container\Ioc;
use Mongolid\Util\ObjectIdUtils;
use Mongolid\Util\SequenceService;

/**
 * A schema maps to a MongoDB collection and defines the shape of the documents
 * within that collection.
 */
abstract class Schema
{
    /**
     * The $dynamic property tells if the schema will accept additional fields
     * that are not specified in the $fields property. This is useful if you
     * does not have a strict document format or if you want to take full
     * advantage of the "schemaless" nature of MongoDB.
     *
     * @var bool
     */
    public $dynamic = false;

    /**
     * Name of the collection where this kind of document is going to be saved
     * or retrieved from.
     *
     * @var string
     */
    public $collection = null;

    /**
     * Tells how a document should look like. If an scalar type is used, it will
     * perform a cast into the value. Otherwise the schema will use the type as
     * the name of the method to be called. See 'objectId' method for example.
     * The last option is to define a field as another schema by using the
     * syntax 'schema.<Class>' This represents an embedded document (or
     * sub-document).
     *
     * @var string[]
     */
    public $fields = [
        '_id' => 'objectId', // Means that the _id will pass trough the `objectId` method
        'created_at' => 'createdAtTimestamp', // Generates an automatic timestamp
        'updated_at' => 'updatedAtTimestamp',
    ];

    /**
     * Name of the class that will be used to represent a document of this
     * Schema when retrieve from the database.
     *
     * @var string
     */
    public $entityClass = 'stdClass';

    /**
     * Filters any field in the $fields that has it's value specified as a
     * 'objectId'. It will wraps the $value, if any, into a ObjectId object.
     *
     * @param mixed $value value that may be converted to ObjectId
     *
     * @return ObjectId|mixed
     */
    public function objectId($value = null)
    {
        if (null === $value) {
            return new ObjectId();
        }

        if (is_string($value) && ObjectIdUtils::isObjectId($value)) {
            $value = new ObjectId($value);
        }

        return $value;
    }

    /**
     * Prepares the field to have a sequence. If $value is zero or not defined
     * a new auto-increment number will be "generated" for the collection of
     * the schema. The sequence generation is done by the SequenceService.
     *
     * @param int|null $value value that will be evaluated
     *
     * @return int
     */
    public function sequence(int $value = null)
    {
        if ($value) {
            return $value;
        }

        return Container::make(SequenceService::class)
            ->getNextValue($this->collection ?: $this->entityClass);
    }

    /**
     * Prepares the field to be the datetime that the document has been created.
     *
     * @param mixed|null $value value that will be evaluated
     *
     * @return UTCDateTime
     */
    public function createdAtTimestamp($value)
    {
        if ($value instanceof UTCDateTime) {
            return $value;
        }

        return $this->updatedAtTimestamp();
    }

    /**
     * Prepares the field to be now.
     *
     * @return UTCDateTime
     */
    public function updatedAtTimestamp()
    {
        return new UTCDateTime();
    }
}
