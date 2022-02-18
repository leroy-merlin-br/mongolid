<?php
namespace Mongolid\Tests\Stubs;

use Mongolid\ActiveRecord;

class LegacyEmbeddedUser extends ActiveRecord
{
    /**
     * @var string
     */
    protected $collection = 'users';

    /**
     * @var bool
     */
    protected $timestamps = true;

    public function parent()
    {
        return $this->embedsOne(LegacyEmbeddedUser::class);
    }

    public function siblings()
    {
        return $this->embedsMany(LegacyEmbeddedUser::class);
    }

    public function son()
    {
        return $this->embedsOne(LegacyEmbeddedUser::class, 'arbitrary_field');
    }

    public function grandsons()
    {
        return $this->referencesMany(LegacyEmbeddedUser::class, 'other_arbitrary_field');
    }

    public function sameName()
    {
        $this->embedsOne(LegacyEmbeddedUser::class, 'sameName');
    }
}
