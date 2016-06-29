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
     * @var MongoUTCDateTime
     */
    protected $mongoDate;

    /**
     * Constructor
     *
     * @param MongoUTCDateTime $mongoDate Object to convert.
     */
    public function __construct(MongoUTCDateTime $mongoDate)
    {
        $this->mongoDate = $mongoDate;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->getFormattedDate());
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
        $date = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            unserialize($data)
        );
        $this->mongoDate = new MongoUTCDateTime($date->getTimestamp()*1000);
    }

    /**
     * {@inheritdoc}
     *
     * @return MongoUTCDateTime
     */
    public function convert()
    {
        return $this->mongoDate;
    }

    /**
     * Retrieves formated date string
     *
     * @return string
     */
    protected function getFormattedDate()
    {
        return $this->mongoDate->toDateTime()->format('Y-m-d H:i:s');
    }
}
