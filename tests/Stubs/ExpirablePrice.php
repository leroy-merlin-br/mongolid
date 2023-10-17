<?php

namespace Mongolid\Tests\Stubs;

use DateTime;

class ExpirablePrice extends Price
{
    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
