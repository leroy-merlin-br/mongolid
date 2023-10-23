<?php

namespace Mongolid\Tests\Stubs;

use DateTime;

class ExpirablePrice extends Price
{
    protected array $casts = [
        'expires_at' => 'datetime',
    ];
}
