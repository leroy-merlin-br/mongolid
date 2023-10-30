<?php

namespace Mongolid\Model\Casts;

use Mongolid\Container\Container;

class CastResolver
{

    public static function resolve(string $castIdentifier): CastInterface
    {
        return Container::get(CastResolverInterface::class)->resolve($castIdentifier);
    }
}
