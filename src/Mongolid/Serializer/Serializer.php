<?php
namespace Mongolid\Serializer;

use MongoDB\BSON\ObjectID as MongoObjectID;
use MongoDB\BSON\UTCDateTime as MongoUTCDateTime;
use Mongolid\Serializer\SerializableTypeInterface;
use Mongolid\Serializer\Type\ObjectID;
use Mongolid\Serializer\Type\UTCDateTime;

/**
 * This class is responsible to serialize ActiveRecord classes. It's necessary
 * due to a bug in Mongo Driver that doesn't allow us to serialize some classes,
 * i.e., ObjectID, UTCDateTime
 *
 * It's a bug present in 1.1 version and should be fixed in version 1.2, so,
 * this class can be deleted after upgrade our dependency of 1.1 version of
 * MongoDB driver
 *
 * @see https://jira.mongodb.org/browse/PHPC-460
 */
class Serializer implements ConvertableInterface
{
    /**
     * @var string[]
     */
    protected $mappedTypes = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->mappedTypes = [
            ObjectID::class    => MongoObjectID::class,
            UTCDateTime::class => MongoUTCDateTime::class,
        ];
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
        array_walk_recursive($attributes, function (&$value) {
            $className = $this->getReflectionClass($value);
            if (class_exists($className)) {
                return $value = new $className($value);
            }
        });

        return serialize($attributes);
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
        $attributes = unserialize($data);
        array_walk_recursive($attributes, function (&$value, $key) {
            if ($value instanceof SerializableTypeInterface) {
                $value = $value->convert();
            }
        });

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(array $data)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function unconvert(array $data)
    {
        return [];
    }

    /**
     * Checks if the given parameter is a mapped type and return its index.
     *
     * @param  mixed $value Value of array to check.
     *
     * @return boolean|integer
     */
    protected function getReflectionClass($value)
    {
        if (false === is_object($value)) {
            return false;
        }

        return array_search(get_class($value), $this->mappedTypes);
    }
}
