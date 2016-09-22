<?php

namespace Mongolid\Serializer\Type;

use MongoDB\BSON\ObjectID as MongoObjectID;
use MongoDB\BSON\UTCDateTime as MongoUTCDateTime;
use Mongolid\Serializer\SerializableTypeInterface;

/**
 * This class is responsible to convert MongoDB objects to types of our domain
 * and vice-versa.
 */
class Converter
{
    /**
     * @var string[]
     */
    protected $mappedTypes = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->mappedTypes = [
            ObjectID::class    => MongoObjectID::class,
            UTCDateTime::class => MongoUTCDateTime::class,
        ];
    }

    /**
     * Converts recursively the given data to persistable objects into database.
     * Example: converts Type\ObjectID to MongoDB\BSON\ObjectID.
     *
     * @param array $data Array to convert.
     *
     * @return mixed
     */
    public function toMongoTypes(array $data)
    {
        array_walk_recursive($data, function (&$value) {
            if (!is_object($value)) {
                return;
            }

            if ($value instanceof SerializableTypeInterface) {
                $value = $value->convert();
            }
        });

        return $data;
    }

    /**
     * Unconverts recursively the given objects (probably retrieved from MongoDB)
     * to our specific types.
     *
     * Example: converts MongoDB\BSON\ObjectID to Type\ObjectID
     *
     * @param array $data Array to convert.
     *
     * @return mixed
     */
    public function toDomainTypes(array $data)
    {
        array_walk_recursive($data, function (&$value) {
            if (!is_object($value)) {
                return;
            }

            $className = $this->getReflectionClass(get_class($value));

            if (!$className) {
                return;
            }

            return $value = new $className($value);
        });

        return $data;
    }

    /**
     * Returns the mapped type of the given className.
     *
     * @param mixed $className Name of the class to return.
     *
     * @return mixed
     */
    protected function getReflectionClass($className)
    {
        return array_search($className, $this->mappedTypes);
    }
}
