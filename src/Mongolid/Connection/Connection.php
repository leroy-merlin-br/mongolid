<?php
namespace Mongolid\Connection;

use Mongolid\Container\Ioc;
use MongoDB\Client;

/**
 * Represents a single connection with the database
 *
 * @package  Mongolid
 */
class Connection
{
    /**
     * The raw MongoDB\Client object that represents this connection
     *
     * @var Client
     */
    protected $rawConnection;

    /**
     * The default database where mongolid will store the documents
     *
     * @var string
     */
    public $defaultDatabase = 'mongolid';

    /**
     * Constructs a new Mongolid connection. It uses the same constructor
     * parameters as the original MongoDB\Client constructor
     *
     * @see   http://php.net/manual/en/mongodb-driver-manager.construct.php
     *
     * @param string $server         The specified connection string.
     * @param array  $options        The mongodb client options.
     * @param array  $driver_options The mongodb driver options when opening a connection.
     */
    public function __construct(
        string $server = "mongodb://localhost:27017",
        array $options = ["connect" => true],
        array $driver_options = []
    ) {
        // In order to work with PHP arrays instead of with objects
        $driver_options['typeMap'] = ['array' => 'array', 'document' => 'array'];

        $this->findDefaultDatabase($server);

        $this->rawConnection = Ioc::make(
            Client::class,
            [$server, $options, $driver_options]
        );
    }

    /**
     * Find and stores the default database in the connection string
     *
     * @param  string $connectionString MongoDB connection string.
     *
     * @return void
     */
    protected function findDefaultDatabase(string $connectionString)
    {
        preg_match("/\\S+\\/(\\w*)/", $connectionString, $matches);

        if ($matches[1] ?? null) {
            $this->defaultDatabase = $matches[1];
        }
    }

    /**
     * Getter for Client instance
     *
     * @return Client
     */
    public function getRawConnection()
    {
        return $this->rawConnection;
    }
}
