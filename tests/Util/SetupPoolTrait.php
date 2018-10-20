<?php
namespace Mongolid\Tests\Util;

use Mongolid\Connection\Connection;
use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc;

trait SetupPoolTrait
{
    public function setupPool(string $host, string $database)
    {
        Ioc::singleton(
            Pool::class,
            function () use ($host, $database) {
                $connection = new Connection("mongodb://{$host}:27017/{$database}");
                $connection->defaultDatabase = $database;

                $pool = new Pool();
                $pool->addConnection($connection);

                return $pool;
            }
        );
    }
}
