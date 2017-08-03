<?php

namespace Mongolid\Connection;

use Mongolid\Container\Ioc;

/**
 * Holds one or more connections and retrieve then as needed. It contains a
 * cache of connections maintained so that the connections can be reused when
 * future requests to the database are required.
 */
class Pool
{
    /**
     * Opened connections.
     *
     * @var SplQueue
     */
    protected $connections;

    /**
     * Constructs a connection pool.
     */
    public function __construct()
    {
        $this->connections = Ioc::make('SplQueue');
    }

    /**
     * Gets a connection from the pool. It will cycle through the existent
     * connections.
     *
     * @return Connection
     */
    public function getConnection()
    {
        if ($chosenConn = $this->connections->pop()) {
            $this->connections->push($chosenConn);

            return $chosenConn;
        }
    }

    /**
     * Adds a new connection to the pool.
     *
     * @param Connection $conn the actual connection that will be added to the pool
     *
     * @return bool Success
     */
    public function addConnection(Connection $conn)
    {
        $this->connections->push($conn);

        return true;
    }
}
