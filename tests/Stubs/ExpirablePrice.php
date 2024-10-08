<?php

namespace Mongolid\Tests\Stubs;

class ExpirablePrice extends Price
{
    /**
     * @var array|string[]
     */
    protected array $casts = [
        'expires_at' => 'datetime',
    ];
}
