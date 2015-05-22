<?php
namespace Mongolid\Connection;

use MongoConnectionException;
use Mongolid\Container\Ioc;
use MongoClient;

/**
 * Represents a single connection with the database
 *
 * @package  Mongolid
 */
class Connection
{
    /**
     * The raw MongoClient object that represents this connection
     *
     * @var MongoClient
     */
    protected $rawConnection;

    /**
     * Constructs a new Mongolid connection. It uses the same constructor
     * parameters as the original MongoClient constructor
     *
     * @see   http://php.net/manual/pt_BR/mongoclient.construct.php
     *
     * @param string $server Connection string
     * @param array  $options
     * @param array  $driver_options
     */
    public function __construct(
        $server = "mongodb://localhost:27017",
        $options = ["connect" => TRUE],
        $driver_options = []
    ) {
        $this->rawConnection = Ioc::make(
            'MongoClient',
            [$server, $options, $driver_options]
        );
    }

    /**
     * Getter for MongoClient instance
     *
     * @return MongoClient
     */
    public function getRawConnection()
    {
        return $this->rawConnection;
    }
}
