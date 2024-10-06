<?php

namespace Mongolid\Tests\Util;

use Mongolid\Connection\Connection;
use Mongolid\Container\Container;

trait DropDatabaseTrait
{
    public function dropDatabase(): void
    {
        $connection = Container::make(Connection::class);

        $connection
            ->getClient()
            ->dropDatabase($connection->defaultDatabase);
    }
}
