<?php
namespace Mongolid\Connection;

use MongoConnectionException;
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
     * @param string $server Connection string
     * @param array  $options
     * @param array  $driver_options
     */
    public function __construct(
        $server = "mongodb://localhost:27017",
        $options = ["connect" => true],
        $driver_options = []
    ) {
        // In order to work with PHP arrays instead of with objects
        $driver_options['typeMap'] = ['array' => 'array', 'document' => 'array'];

        $this->rawConnection = Ioc::make(
            Client::class,
            [$server, $options, $driver_options]
        );
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
