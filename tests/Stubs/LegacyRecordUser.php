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

    protected $fillable = [
        'name'
    ];

    public function siblings()
    {
        return $this->embedsMany(LegacyRecordUser::class);
    }

    public function grandsons()
    {
        return $this->referencesMany(LegacyRecordUser::class, 'grandsons');
    }
}
