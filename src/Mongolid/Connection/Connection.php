<?php

namespace Mongolid\Connection;

use MongoDB\Client;
use MongoDB\Driver\Manager;

/**
 * Represents a single connection with the database.
 */
class Connection
{
    /**
     * The raw MongoDB\Client object that represents this connection.
     *
     * @var Client
     */
    protected $rawConnection;

    /**
     * The default database where mongolid will store the documents.
     *
     * @var string
     */
    public $defaultDatabase = 'mongolid';

    /**
     * Constructs a new Mongolid connection. It uses the same constructor
     * parameters as the original MongoDB\Client constructor.
     *
     * @see   http://php.net/manual/en/mongodb-driver-manager.construct.php
     *
     * @param string $server        the specified connection string
     * @param array  $options       the mongodb client options
     * @param array  $driverOptions the mongodb driver options when opening a connection
     */
    public function __construct(
        string $server = 'mongodb://localhost:27017',
        array $options = [],
        array $driverOptions = []
    ) {
        // In order to work with PHP arrays instead of with objects
        $driverOptions['typeMap'] = ['array' => 'array', 'document' => 'array'];

        $this->findDefaultDatabase($server);

        $this->rawConnection = new Client($server, $options, $driverOptions);
    }

    /**
     * Find and stores the default database in the connection string.
     *
     * @param string $connectionString mongoDB connection string
     */
    protected function findDefaultDatabase(string $connectionString)
    {
        preg_match('/\S+\/(\w*)/', $connectionString, $matches);

        if ($matches[1] ?? null) {
            $this->defaultDatabase = $matches[1];
        }
    }

    /**
     * Getter for Client instance.
     *
     * @return Client
     */
    public function getRawConnection()
    {
        return $this->rawConnection;
    }

    /**
     * Getter for Manager instance.
     *
     * @return Manager
     */
    public function getRawManager()
    {
        return $this->getRawConnection()->getManager();
    }
}
