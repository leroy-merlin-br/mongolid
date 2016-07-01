<?php
namespace Mongolid\Serializer;

use Mongolid\Serializer\Type\Converter;

/**
 * This class is responsible to serialize ActiveRecord classes. It is necessary
 * due to a bug in Mongo Driver that does not allow us to serialize some classes,
 * i.e., ObjectID, UTCDateTime
 *
 * It's a bug present in 1.1 version and should be fixed in version 1.2, so,
 * this class can be deleted after upgrade our dependency of 1.1 version of
 * MongoDB driver
 *
 * @see https://jira.mongodb.org/browse/PHPC-460
 */
class Serializer
{
    /**
     * @var Converter
     */
    protected $converter;

    /**
     * Constructor
     *
     * @param Converter $converter Class responsible to convert objects.
     */
    public function __construct(Converter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * Walk into an array to get not serializable objects and replace it by a
     * serializable one.
     *
     * @param  array $attributes ActiveRecord attributes to be serialized.
     *
     * @return string
     */
    public function serialize(array $attributes): string
    {
        return serialize($this->convert($attributes));
    }

    /**
     * Unserializes the given string and turn it back to specific objects.
     *
     * @param  string $data Serialized string to be converted.
     *
     * @return array
     */
    public function unserialize(string $data): array
    {
        return $this->unconvert(unserialize($data));
    }

    /**
     * Converts recursively the given objects (probably retrieved from MongoDB)
     * to our specific types.
     * Example: converts MongoDB\BSON\ObjectID to Type\ObjectID
     *
     * @param  array $attributes Array to convert.
     *
     * @return array
     */
    public function convert(array $attributes)
    {
        return $this->converter->toDomainTypes($attributes);
    }

    /**
     * Unconverts recursively the given objects to persistable objects on MongoDB
     * Example: converts Type\ObjectID to MongoDB\BSON\ObjectID
     *
     * @param  array $attributes Array to convert.
     *
     * @return array
     */
    public function unconvert(array $attributes)
    {
        return $this->converter->toMongoTypes($attributes);
    }
}
