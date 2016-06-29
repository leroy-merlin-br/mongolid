<?php
namespace Mongolid\Serializer\Type;

use DateTime;
use InvalidArgumentException;
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
     * @var integer
     */
    protected $timestamp;

    /**
     * Constructor
     *
     * @param integer|MongoUTCDateTime|null $datetime MongoUTCDateTime or Timestamp to wrap. If it was null, uses
     *                                                current timestamp
     *
     * @throws InvalidArgumentException $datetime accepts only integer, null or MongoUTCDateTime.
     */
    public function __construct($datetime = null)
    {
        if (is_null($datetime)) {
            $datetime = time();
        }

        if (is_integer($datetime)) {
            $this->timestamp = $datetime * 1000;
            $this->mongoDate = new MongoUTCDateTime($this->timestamp);

            return;
        }

        if ($datetime instanceof MongoUTCDateTime) {
            $this->mongoDate = $datetime;
            $this->timestamp = $datetime->toDateTime()->getTimestamp() * 1000;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument type given. Constructor allows only integer, '.
            'null or MongoDB\BSON\UTCDateTime'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->timestamp);
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
        $this->timestamp = unserialize($data);
        $this->mongoDate = new MongoUTCDateTime($this->timestamp);
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
     * A wrapper to use this class as MongoUTCDateTime
     *
     * @param  string $method Method of MongoUTCDateTime to call.
     * @param  array  $args   Arguments to pass.
     *
     * @return mixed
     */
    public function __call($method, array $args = [])
    {
        return call_user_func_array([$this->mongoDate, $method], $args);
    }
}
