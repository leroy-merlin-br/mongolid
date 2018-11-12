<?php
namespace Mongolid\Tests\Integration\Stubs;

use MongoDB\Collection;
use Mongolid\Connection\Connection;
use Mongolid\Container\Ioc;
use Mongolid\Model\AbstractActiveRecord;

class User extends AbstractActiveRecord
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
        $connection = Ioc::make(Connection::class);
        $client = $connection->getRawConnection();

        return $client->{$connection->defaultDatabase}->{$this->collection};
    }

    public function parent()
    {
        return $this->referencesOne(User::class, 'parent_id');
    }

    public function siblings()
    {
        return $this->referencesMany(User::class, 'siblings_ids');
    }

    public function son()
    {
        return $this->referencesOne(User::class, 'son_id', 'code');
    }

    public function grandsons()
    {
        return $this->referencesMany(User::class, 'grandsons_ids', 'code');
    }
}
