<?php

namespace Mongolid\Tests\Stubs;

class PolymorphedReferencedUser extends ReferencedUser
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'type',
        'new_field',
    ];
}
