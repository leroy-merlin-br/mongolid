<?php

namespace Mongolid\Util;

use MongoDB\Collection;
use Mongolid\Connection\Pool;

class SequenceService
{
    /**
     * Sequences collection name on MongoDB. Default 'mongolid_sequences'
     *
     * @var string
     */
    protected $collection;

    /**
     * Connections that are going to be used to interact with the database
     *
     * @var Pool
     */
    protected $connPool;

    /**
     * @param Pool        $connPool The connections that are going to be used to interact with the database
     * @param string      $collection The colection where the sequences will be stored.
     */
    public function __construct(Pool $connPool, $collection = 'mongolid_sequences')
    {
        $this->connPool   = $connPool;
        $this->collection = $collection;
    }

    /**
     * Get next value for the sequence
     *
     * @param string $sequenceName
     *
     * @return int
     */
    public function getNextValue($sequenceName): int
    {
        $sequenceValue = $this->rawCollection()->findOneAndUpdate(
            ['_id' => $sequenceName],
            ['$inc' => ['seq' => 1]],
            ['upsert' => true]
        );

        if ($sequenceValue) {
            $_id = $sequenceValue->seq + 1;
        }

        return $_id ?? 1;
    }

    /**
     * Get the actual MongoDB Collection object
     *
     * @return Collection
     */
    protected function rawCollection(): Collection
    {
        $conn          = $this->connPool->getConnection();
        $database      = $conn->defaultDatabase;
        return $conn->getRawConnection()->$database->{$this->collection};
    }
}
