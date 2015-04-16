<?php namespace Mongolid\Mongolid\Connection;

use MongoConnectionException;
use Mongolid\Mongolid\Container\Ioc;

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
     * Creates a new connection with database.
     *
     * @param  string $connectionString
     * @return MongoClient
     */
    public function createConnection($connectionString = '')
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

        return $connection;
    }
}
