<?php
namespace Mongolid\Serializer\Type;

use InvalidArgumentException;
use JsonSerializable;
use MongoDB\BSON\ObjectID as MongoObjectID;
use Mongolid\Serializer\SerializableTypeInterface;
use Mongolid\Util\ObjectIdUtils;

/**
 * This class is a workaround to make real Mongo ObjectID serializable
 */
class ObjectID implements SerializableTypeInterface, JsonSerializable
{
    /**
     * @var string
     */
    protected $objectIdString;

    /**
     * Constructor
     *
     * @throws InvalidArgumentException If $mongoId is not valid.
     *
     * @param MongoObjectID|string $mongoId MongoDB ObjectID or a string.
     */
    public function __construct($mongoId = null)
    {
        if (! $mongoId) {
            $mongoId = new MongoObjectID;
        }

        if (is_object($mongoId)) {
            $mongoId = (string) $mongoId;
        }

        if (! ObjectIdUtils::isObjectId($mongoId)) {
            throw new InvalidArgumentException("Invalid BSON ID provided");
        }

        $this->objectIdString = $mongoId;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->objectIdString);
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
        $this->objectIdString = unserialize($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return MongoObjectID
     */
    public function convert()
    {
        return new MongoObjectID($this->objectIdString);
    }

    /**
     * Returns the hexidecimal representation of this ObjectID
     *
     * @return string
     */
    public function __toString()
    {
        return $this->objectIdString;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return (string) $this;
    }
}
