<?php
namespace Mongolid\Serializer\Type;

use MongoDB\BSON\ObjectID as MongoObjectID;
use Mongolid\Serializer\SerializableTypeInterface;

/**
 * This class is a workaround to make real Mongo ObjectID serializable
 */
class ObjectID implements SerializableTypeInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * Constructor
     *
     * @param MongoObjectID $mongoId MongoDB ObjectID to serialize.
     */
    public function __construct(MongoObjectID $mongoId)
    {
        $this->id = (string) $mongoId;
    }

    /**
     * Serialies string of ObjectID
     *
     * @return string Serialized id
     */
    public function serialize()
    {
        return serialize($this->id);
    }

    /**
     * Unserializes the object id string
     *
     * @param  string $data Serialized object id.
     *
     * @return void
     */
    public function unserialize($data)
    {
        $this->id = unserialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function convert()
    {
        return new MongoObjectID($this->id);
    }
}
