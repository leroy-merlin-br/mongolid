<?php

declare(strict_types=1);

namespace Mongolid\Connection;

use MongoDB\Client;

/**
 * Represents a single connection with the database.
 */
class Connection
{
    /**
     * The default database where mongolid will store the documents.
     */
    public string $defaultDatabase = 'mongolid';

    /**
     * MongoDB Client object that represents this connection.
     */
    protected ?Client $client = null;

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
        private string $server = 'mongodb://localhost:27017',
        private array $options = [],
        private array $driverOptions = []
    ) {
        $this->findDefaultDatabase($server);
    }

    /**
     * Getter for Client instance.
     */
    public function getClient(): Client
    {
        if (!$this->client) {
            // This will make the Mongo Driver return documents as arrays instead of objects
            $this->driverOptions['typeMap'] = ['array' => 'array'];

            $this->client = new Client(
                $this->server,
                $this->options,
                $this->driverOptions
            );
        }

        return $this->client;
    }

    /**
     * Find and stores the default database in the connection string.
     */
    protected function findDefaultDatabase(string $connectionString): void
    {
        preg_match('/\S+\/(\w*)/', $connectionString, $matches);

        if ($matches[1] ?? null) {
            $this->defaultDatabase = $matches[1];
        }
    }
}
