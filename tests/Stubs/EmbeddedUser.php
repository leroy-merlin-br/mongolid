<?php
namespace Mongolid\Tests\Stubs;

use MongoDB\Collection;
use Mongolid\Connection\Connection;
use Mongolid\Container\Container;
use Mongolid\Model\AbstractModel;

class EmbeddedUser extends AbstractModel
{
    /**
     * @var string
     */
    protected $collection = 'users';

    /**
     * @var bool
     */
    protected $timestamps = true;

    public function collection(): Collection
    {
        $connection = Container::make(Connection::class);
        $client = $connection->getClient();

        return $client->{$connection->defaultDatabase}->{$this->collection};
    }

    public function parent()
    {
        return $this->embedsOne(EmbeddedUser::class);
    }

    public function siblings()
    {
        return $this->embedsMany(EmbeddedUser::class);
    }

    public function son()
    {
        return $this->embedsOne(EmbeddedUser::class, 'arbitrary_field');
    }

    public function grandsons()
    {
        return $this->embedsMany(EmbeddedUser::class, 'other_arbitrary_field');
    }

    public function sameName()
    {
        $this->embedsOne(EmbeddedUser::class, 'sameName');
    }
}
