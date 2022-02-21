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
    public $mutable = true;

    /**
     * @var bool
     */
    protected $timestamps = true;

    /**
     * @var bool
     */
    protected $dynamic = false;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
    ];

    public function siblings()
    {
        return $this->embedsMany(LegacyRecordUser::class);
    }

    public function grandsons()
    {
        return $this->referencesMany(LegacyRecordUser::class, 'grandsons');
    }

    public function setSecretAttribute($value)
    {
        return 'password_override';
    }
}
