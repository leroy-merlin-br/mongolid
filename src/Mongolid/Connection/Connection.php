<?php namespace Mongolid\Connection;

use MongoConnectionException;
use Mongolid\Container\Ioc;

/**
 * Responsable for create a new or reuse a connection with MongoDB.
 */
class Connection
{
    /**
     * MongoClient instance shared.
     *
     * @var MongoClient
     */
    public static $sharedConnection;

    /**
     * Collection's name.
     * @var mixed
     */
    protected $collection = false;

    /**
     * Database's name.
     * @var string
     */
    protected $database = false;

    /**
     * Write concern.
     * @var integer
     */
    protected $writeConcern = 1;

    /**
     * Creates a new connection with database.
     *
     * @param  string $connectionString
     * @return MongoClient
     */
    protected function createConnection($connectionString = '')
    {
        if (static::$sharedConnection) {
            return static::$sharedConnection;
        }

        try {
            $connection = Ioc::make('MongoClient', [$connectionString, []]);
            static::$sharedConnection = $connection;
        } catch (MongoConnectionException $e) {
            throw new MongoConnectionException(
                "Failed to connect with string: $connectionString",
                1,
                $e
            );
        }

        return static::$sharedConnection;
    }

    /**
     * Getter for MongoClient instance
     * @return MongoClient
     */
    public function getConnectionInstance()
    {
        return $this->createConnection();
    }

    /**
     * Returns the collection's name
     * @return mixed
     */
    public function setCollection($collectionName)
    {
        return $this->collection = $collectionName;
    }

    /**
     * Returns the database's name
     * @return mixed
     */
    public function setDatabase($databaseName)
    {
        return $this->database = $databaseName;
    }

    /**
     * Returns the database's name
     * @return mixed
     */
    public function setWriteConcern($w)
    {
        return $this->writeConcern = $w;
    }
}
