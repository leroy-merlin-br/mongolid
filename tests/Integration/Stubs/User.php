<?php
namespace Mongolid\Tests\Integration\Stubs;

use MongoDB\Collection;
use Mongolid\ActiveRecord;
use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc;

class User extends ActiveRecord
{
    /**
     * @var string
     */
    protected $collection = 'users';

    /**
     * @var array
     */
    protected $fields = [
        '_id' => 'objectId',
    ];

    public function collection(): Collection
    {
        $connection = Ioc::make(Pool::class)->getConnection();
        $client = $connection->getRawConnection();

        return $client->{$connection->defaultDatabase}->{$this->collection};
    }
}
