<?php
namespace Mongolid\Util;

use MongoDB\Collection;
use Mongolid\Connection\Connection;

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
     * Connection that is going to be used to interact with the database.
     *
     * @var Connection
     */
    protected $connection;

    public function __construct(Connection $connection, string $collection = 'mongolid_sequences')
    {
        $this->connection = $connection;
        $this->collection = $collection;
    }

    /**
     * Get next value for the sequence.
     *
     * @param string $sequenceName sequence identifier string
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
     */
    protected function rawCollection(): Collection
    {
        $database = $this->connection->defaultDatabase;

        return $this->connection->getClient()
            ->$database
            ->{$this->collection};
    }
}
