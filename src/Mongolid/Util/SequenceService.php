<?php

namespace Mongolid\Util;

use MongoDB\Collection;
use Mongolid\Connection\Pool;

/**
 * Sequence service will manage and provide auto-increment sequences to be used
 * by the models. It can be useful for objects which the _id must be an integer
 * sequence.
 */
class SequenceService
{
    /**
     * Sequences collection name on MongoDB. Default 'mongolid_sequences'.
     *
     * @var string
     */
    protected $collection;

    /**
     * Connections that are going to be used to interact with the database.
     *
     * @var Pool
     */
    protected $connPool;

    /**
     * @param Pool   $connPool   the connections that are going to be used to interact with the database
     * @param string $collection the collection where the sequences will be stored
     */
    public function __construct(Pool $connPool, string $collection = 'mongolid_sequences')
    {
        $this->connPool = $connPool;
        $this->collection = $collection;
    }

    /**
     * Get next value for the sequence.
     *
     * @param string $sequenceName sequence identifier string
     *
     * @return int
     */
    public function getNextValue(string $sequenceName): int
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
     * Get the actual MongoDB Collection object.
     *
     * @return Collection
     */
    protected function rawCollection(): Collection
    {
        $conn = $this->connPool->getConnection();
        $database = $conn->defaultDatabase;

        return $conn->getRawConnection()->$database->{$this->collection};
    }
}
