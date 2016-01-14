<?php
namespace Zizaco\Mongolid;

use MongoClient;
use MongoDB;
use MongoConnectionException;

class MongoDbConnector
{
    /**
     * Stores the open connections with the connection string as the
     * key.
     *
     * @var MongoClient[]
     */
    public $connections = [];

    /**
     * Default connection string that will be used if no connection
     * string is specified
     *
     * @var string
     */
    public $defaultConnectionString;

    /**
     * Returns the MongoClient object (the database connection itself). It will create one
     * based in the given connection string if it doesn't exists.
     *
     * @var string $connectionString
     *
     * @return MongoClient
     */
    public function getConnection($connectionString = null)
    {
        $connectionString = $this->checkDefaultConnectionString($connectionString);

        if (isset($this->connections[$connectionString]) && $this->connections[$connectionString]) {
            return $this->connections[$connectionString];
        }

        return $this->connections[$connectionString] = $this->newMongoClient($connectionString);
    }

    /**
     * Figure out if the default connection string should be set or used.
     * Returns a connection string
     *
     * @param  string $connectionString
     *
     * @return string The connection string that should actually be used
     * @throws \MongoConnectionException
     */
    protected function checkDefaultConnectionString($connectionString)
    {
        if (!$connectionString && !$this->defaultConnectionString) {
            throw new MongoConnectionException("No connection string has been specified");
        }

        if (!$this->defaultConnectionString) {
            $this->defaultConnectionString = $connectionString;
        }

        if (!$connectionString) {
            return $this->defaultConnectionString;
        }

        return $connectionString;
    }

    protected function newMongoClient($connectionString)
    {
        return new MongoClient($connectionString);
    }
}
