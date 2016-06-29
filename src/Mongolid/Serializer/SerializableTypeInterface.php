<?php
namespace Mongolid\Serializer;

use Serializable;

/**
 * This interface is a contract to convert unserialized objects to corresponding
 * MongoDB\BSON objects
 */
interface SerializableTypeInterface extends Serializable
{
    /**
     * Retrieves corresponding MongoDB object that according to class that
     * implement this interface, i.e., Type\ObjectID should return an
     * MongoDB\BSON\ObjectID object
     *
     * @return mixed
     */
    public function convert();
}
