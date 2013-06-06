<?php
namespace Zizaco\Mongolid;

use MongoClient;

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
     */
    public function getConnection( $connectionString = '' )
    {
        // If exists in $shared_connection, use it
        if( MongoDbConnector::$shared_connection ) {
            $connection = MongoDbConnector::$shared_connection;
        } else {
            // Else, connect and place connection in $shared_connection
            try{
                $connection = new MongoClient($connectionString);
            } catch(\MongoConnectionException $e) {
                trigger_error('Failed to connect with string: "'.$connectionString.'"');
            }

            MongoDbConnector::$shared_connection = $connection;
        }

        return $connection;
    }
}