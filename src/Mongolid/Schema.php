<?php
namespace Mongolid;

use Mongolid\Container\Ioc;
use Mongolid\Serializer\Type\ObjectID;
use Mongolid\Serializer\Type\UTCDateTime;
use Mongolid\Util\ObjectIdUtils;
use Mongolid\Util\SequenceService;

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
     * that are not specified in the $fields property. This is useful if you
     * does not have a strict document format or if you want to take full
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
     * perform a cast into the value. Otherwise the schema will use the type as
     * the name of the method to be called. See 'objectId' method for example.
     * The last option is to define a field as another schema by using the
     * syntax 'schema.<Class>' This represents an embedded document (or
     * sub-document).
     *
     * @var string[]
     */
    public $fields  = [
        '_id' => 'objectId', // Means that the _id will pass trough the `objectId` method
        'created_at' => 'createdAtTimestamp', // Generates an automatic timestamp
        'updated_at' => 'updatedAtTimestamp'
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
     * @param  mixed $value Value that may be converted to ObjectID.
     *
     * @return ObjectID|mixed
     */
    public function objectId($value = null)
    {
        if ($value === null) {
            return new ObjectID();
        }

        if (is_string($value) && ObjectIdUtils::isObjectId($value)) {
            $value = new ObjectID($value);
        }

        return $value;
    }

    /**
     * Prepares the field to have a sequence. If $value is zero or not defined
     * a new auto-increment number will be "generated" for the collection of
     * the schema. The sequence generation is done by the SequenceService.
     *
     * @param  integer|null $value Value that will be evaluated.
     *
     * @return integer
     */
    public function sequence(int $value = null)
    {
        if ($value) {
            return $value;
        }

        return Ioc::make(SequenceService::class)
            ->getNextValue($this->collection);
    }

    /**
     * Prepares the field to be the datetime that the document has been created
     *
     * @param  mixed|null $value Value that will be evaluated.
     *
     * @return UTCDateTime
     */
    public function createdAtTimestamp($value)
    {
        if ($value instanceof UTCDateTime) {
            return $value;
        }

        return $this->updatedAtTimestamp(null);
    }

    /**
     * Prepares the field to be now
     *
     * @return UTCDateTime
     */
    public function updatedAtTimestamp()
    {
        return new UTCDateTime;
    }
}
