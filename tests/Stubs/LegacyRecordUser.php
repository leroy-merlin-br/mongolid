<?php

namespace Mongolid\Tests\Stubs;

use Mongolid\LegacyRecord;

class LegacyRecordUser extends LegacyRecord
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
        return $this->embedsOne(LegacyRecordUser::class);
    }

    public function siblings()
    {
        return $this->embedsMany(LegacyRecordUser::class);
    }

    public function son()
    {
        return $this->embedsOne(LegacyRecordUser::class, 'arbitrary_field');
    }

    public function grandsons()
    {
        return $this->referencesMany(LegacyRecordUser::class, 'other_arbitrary_field');
    }

    public function sameName()
    {
        $this->embedsOne(LegacyRecordUser::class, 'sameName');
    }
}
