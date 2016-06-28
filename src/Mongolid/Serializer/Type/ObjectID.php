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
     * {@inheritdoc}
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->id);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $data Serialized string to parse.
     *
     * @return void
     */
    public function unserialize($data)
    {
        $this->id = unserialize($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return MongoObjectID
     */
    public function convert()
    {
        return new MongoObjectID($this->id);
    }
}
