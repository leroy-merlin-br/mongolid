<?php
namespace Mongolid\Serializer\Type;

use DateTime;
use MongoDB\BSON\UTCDateTime as MongoUTCDateTime;
use Mongolid\Serializer\SerializableTypeInterface;

/**
 * This class is a workaround to make real Mongo UTCDateTime serializable
 */
class UTCDateTime implements SerializableTypeInterface
{
    /**
     * @var string
     */
    protected $date;

    public function __construct(MongoUTCDateTime $mongoDate)
    {
        $this->date = $mongoDate->toDateTime()->format('Y-m-d H:i:s');
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->date);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($data)
    {
        $this->date = unserialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function convert()
    {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $this->date);

        return new MongoUTCDateTime($date->getTimestamp()*1000);
    }
}
