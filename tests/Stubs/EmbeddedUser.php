<?php
namespace Mongolid\Tests\Stubs;

use Mongolid\Model\AbstractModel;

class EmbeddedUser extends AbstractModel
{
    protected ?string $collection = 'users';

    protected bool $timestamps = true;

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
