<?php
namespace Mongolid\Tests\Integration\Stubs;

use MongoDB\Collection;
use Mongolid\Connection\Connection;
use Mongolid\Container\Ioc;
use Mongolid\Model\AbstractModel;

class ReferencedUser extends AbstractModel
{
    /**
     * @var string
     */
    protected $collection = 'users';

    /**
     * @var bool
     */
    protected $timestamps = false;

    public function collection(): Collection
    {
        $connection = Ioc::make(Connection::class);
        $client = $connection->getRawConnection();

        return $client->{$connection->defaultDatabase}->{$this->collection};
    }

    public function parent()
    {
        return $this->referencesOne(ReferencedUser::class);
    }

    public function siblings()
    {
        return $this->referencesMany(ReferencedUser::class);
    }

    public function son()
    {
        return $this->referencesOne(ReferencedUser::class, 'arbitrary_field', 'code');
    }

    public function grandsons()
    {
        return $this->referencesMany(ReferencedUser::class, null, 'code');
    }

    public function invalid()
    {
        return 'I am not a relation!';
    }
}
