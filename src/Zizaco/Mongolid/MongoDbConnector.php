<?php
namespace Zizaco\Mongolid;

use MongoClient;
use MongoDB;

class MongoDbConnector
{
    /**
     * The connection that will be used.
     *
     * @var MongoDB
     */
    public static $shared_connection;

    /**
     * Returns the connection. If non existent then create it
     *
     * @var MongoDB
     * @return MongoClient|MongoDB
     */
    public function getConnection($connectionString = '')
    {
        // If exists in $shared_connection, use it
        if (MongoDbConnector::$shared_connection) {
            $connection = MongoDbConnector::$shared_connection;
        } else {
            // Else, connect and place connection in $shared_connection
            try {
                $connection = new MongoClient($connectionString);
            } catch (\MongoConnectionException $e) {
                return trigger_error('Failed to connect with string: "' . $connectionString . '"');
            }

            MongoDbConnector::$shared_connection = $connection;
        }

        return $connection;
    }
}
