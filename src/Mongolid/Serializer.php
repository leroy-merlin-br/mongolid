<?php
namespace Mongolid;

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
class Serializer
{
    /**
     * Walk into an array to get not serializable objects and convert it to a
     * serializable one
     *
     * @param  array $attributes ActiveRecord attributes to be serialized
     *
     * @return string
     */
    public function serialize(array $attributes): string
    {
        return serialize($attributes);
    }

    /**
     * Unserializes the given string and turn it back to specific objects
     *
     * @param  string $data Serialized string to be converted
     *
     * @return mixed
     */
    public function unserialize(string $data)
    {
        return unserialize($data);
    }
}
