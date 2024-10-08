<?php

namespace Mongolid\Tests\Util;

use Mongolid\Connection\Connection;
use Mongolid\Container\Container;

trait SetupConnectionTrait
{
    public function setupConnection(string $host, string $database): void
    {
        Container::singleton(
            Connection::class,
            function () use ($host, $database): Connection {
                $connection = new Connection(
                    "mongodb://{$host}:27017/{$database}"
                );
                $connection->defaultDatabase = $database;

                return $connection;
            }
        );
    }
}
