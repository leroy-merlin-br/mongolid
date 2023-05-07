<?php

declare(strict_types=1);

namespace Mongolid\Schema;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Container\Container;
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
     * do not have a strict document format or if you want to take full
     * advantage of the "schemaless" nature of MongoDB.
     */
    public bool $dynamic = false;

    /**
     * Name of the collection where this kind of document is going to be saved
     * or retrieved from.
     */
    public ?string $collection = null;

    /**
     * Tells how a document should look like. If a scalar type is used, it will
     * perform a cast into the value. Otherwise, the schema will use the type as
     * the name of the method to be called. See 'objectId' method for example.
     * The last option is to define a field as another schema by using the
     * syntax 'schema.<Class>' This represents an embedded document (or
     * sub-document).
     *
     * @var string[]
     */
    public array $fields = [
        '_id' => 'objectId', // Means that the _id will pass through the `objectId` method
        'created_at' => 'createdAtTimestamp', // Generates an automatic timestamp
        'updated_at' => 'updatedAtTimestamp',
    ];

    /**
     * Name of the class that will be used to represent a document of this
     * Schema when retrieve from the database.
     */
    public string $entityClass = 'stdClass';

    /**
     * Filters any field in the $fields that has its value specified as a
     * 'objectId'. It will wrap the $value, if any, into a ObjectId object.
     *
     * @param mixed $value value that may be converted to ObjectId
     */
    public function objectId(mixed $value = null): mixed
    {
        if (null === $value) {
            return new ObjectId();
        }

        if (is_string($value) && ObjectIdUtils::isObjectId($value)) {
            return new ObjectId($value);
        }

        return $value;
    }

    /**
     * Prepares the field to have a sequence. If $value is zero or not defined
     * a new auto-increment number will be "generated" for the collection of
     * the schema. The sequence generation is done by the SequenceService.
     *
     * @param int|null $value value that will be evaluated
     */
    public function sequence(?int $value = null): int
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
     */
    public function createdAtTimestamp(mixed $value): UTCDateTime
    {
        if ($value instanceof UTCDateTime) {
            return $value;
        }

        return $this->updatedAtTimestamp();
    }

    /**
     * Prepares the field to be now.
     */
    public function updatedAtTimestamp(): UTCDateTime
    {
        return new UTCDateTime();
    }
}
