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

    /**
     * Constructor
     *
     * @param MongoUTCDateTime $mongoDate Object to convert
     */
    public function __construct(MongoUTCDateTime $mongoDate)
    {
        $this->date = $mongoDate->toDateTime()->format('Y-m-d H:i:s');
    }

    /**
     * Serializes converted date.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->date);
    }

    /**
     * Unserializes the given data
     *
     * @param string $data Converted string date
     *
     * @return void
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
