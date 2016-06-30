<?php
namespace Mongolid\Serializer;

/**
 * This interface is responsible to set a contract between models which have
 * some custom types that should be replaced with specific mongodb objects and
 * the database
 */
interface ConvertableInterface
{
    /**
     * Converts recursively the given data to persistible objects into database.
     * Example: converts Type\ObjectID to MongoDB\BSON\ObjectID
     *
     * @param  array $data Array to convert.
     *
     * @return array
     */
    public function convert(array $data);

    /**
     * Unconverts recursively the given objects (probaly retrieved from MongoDB)
     * to our specific types.
     *
     * Example: converts MongoDB\BSON\ObjectID to Type\ObjectID
     *
     * @param  array $data Array to convert.
     *
     * @return array
     */
    public function unconvert(array $data);
}
