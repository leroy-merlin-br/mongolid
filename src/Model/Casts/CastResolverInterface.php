<?php

namespace Mongolid\Model\Casts;

interface CastResolverInterface
{
    public function resolve(string $castIdentifier): CastInterface;
}
