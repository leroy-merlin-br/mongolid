<?php

namespace Mongolid\Tests\Stubs;

class ExpirablePrice extends Price
{
    protected array $casts = [
        'expires_at' => 'datetime',
    ];
}
