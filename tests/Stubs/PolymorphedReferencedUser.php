<?php

namespace Mongolid\Tests\Stubs;

class PolymorphedReferencedUser extends ReferencedUser
{
    /**
     * @var array|string[]
     */
    protected array $fillable = [
        'type',
        'new_field',
    ];
}
