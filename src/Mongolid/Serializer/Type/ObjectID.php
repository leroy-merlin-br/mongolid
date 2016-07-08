<?php
namespace Mongolid\Serializer\Type;

use MongoDB\BSON\ObjectID as MongoObjectID;
use InvalidArgumentException;
use Mongolid\Container\Ioc;
use Mongolid\Serializer\SerializableTypeInterface;
use Mongolid\Util\ObjectIdUtils;

/**
 * This class is a workaround to make real Mongo ObjectID serializable
 */
class ObjectID implements SerializableTypeInterface
{
    /**
     * @var string
     */
    protected $objecIdString;

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

        if (! Ioc::make(ObjectIdUtils::class)->isObjectId($mongoId)) {
            throw new InvalidArgumentException("Invalid BSON ID provided");
        }

        $this->objecIdString = $mongoId;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->objecIdString);
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
        $this->objecIdString = unserialize($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return MongoObjectID
     */
    public function convert()
    {
        return new MongoObjectID($this->objecIdString);
    }

    /**
     * Returns the hexidecimal representation of this ObjectID
     *
     * @return string
     */
    public function __toString()
    {
        return $this->objecIdString;
    }
}
