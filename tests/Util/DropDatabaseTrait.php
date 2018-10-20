<?php
namespace Mongolid\Tests\Util;

use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc;

trait DropDatabaseTrait
{
    public function dropDatabase()
    {
        $pool = Ioc::make(Pool::class);
        $connection = $pool->getConnection();

        $connection->getRawConnection()
            ->dropDatabase($connection->defaultDatabase);
    }
}
