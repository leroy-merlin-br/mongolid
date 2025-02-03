<?php

namespace Mongolid\Tests\Stubs;

class PolymorphedReferencedUser extends ReferencedUser
{
    protected array $fillable = [
        'type',
        'new_field',
    ];
}
