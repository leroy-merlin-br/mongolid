<?php
namespace Mongolid\Query;

use Mongolid\Model\Model;
use Mongolid\Connection\Connection;

class Builder
{

    /**
     * MongoClient instance.
     * @var MongoClient
     */
    protected $connection;

    /**
     * Constructor
     * @param MongoConnection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $collection;
    }

    /**
     * Performs the save() operation into MongoDB.
     * @return boolean
     */
    public function save(Model $instance)
    {
        if (! $this->canPersistInstance($instance)) {
            return false;
        }

        $options = $this->options();

        $result = $this->collection()
            ->save($instance->sanitizeAttributes(), $options);

        return $this->parseResult($result);
    }

    /**
     * Performs the save() operation into MongoDB.
     * @return boolean
     */
    public function update(Model $instance)
    {
        if (! $this->canPersistInstance($instance)) {
            return false;
        }

        $options = $this->options();

        $result = $this->collection()
            ->update(
                ['_id'  => $instance->getId()],
                ['$set' => $instance->changedAttributes()],
                $options
            );

        return $this->parseResult($result);
    }

    /**
     * Performs the save() operation into MongoDB.
     * @return boolean
     */
    public function delete(Model $instance)
    {
        if (! $this->canPersistInstance($instance)) {
            return false;
        }

        $result = $this->collection()
            ->delete(
                ['_id'  => $instance->getId()]
            );

        return $this->parseResult($result);
    }

    /**
     * Performs the save() operation into MongoDB.
     * @return boolean
     */
    public function insert(Model $instance)
    {
        if (! $this->canPersistInstance($instance)) {
            return false;
        }

        $result = $this->collection()
            ->insert($instance->sanitizeAttributes(), $options);

        return $this->parseResult($result);
    }

    /**
     * Returns the MongoCollection object.
     * @return MongoCollection
     */
    protected function collection()
    {
        return $this->getConnection()
            ->collection();
    }

    /**
     * Get all options to persist anything at MongoDB.
     * @return array
     */
    protected function options()
    {
        return $this->getConnection()->getOptions();
    }

    public function parseResult(array $results)
    {
        return isset($results['ok']) && $results['ok']?: false;
    }

    /**
     * Verifies if this model can be persisted.
     * @param  Model  $instance
     * @return boolean
     */
    public function canPersistInstance(Model $instance)
    {
        return str_len((string)$instance->getCollectionName()) > 0;
    }
}
