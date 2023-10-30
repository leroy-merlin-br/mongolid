<?php

namespace Mongolid\Model\Casts;

use Mongolid\Model\Casts\DateTime\DateTimeCast;
use Mongolid\Model\Casts\DateTime\ImmutableDateTimeCast;
use Mongolid\Model\Casts\Exceptions\InvalidCastException;

class CastResolverCache implements CastResolverInterface
{
    private array $cache = [];

    public function __construct(
        private CastResolverInterface $castResolver
    ) {
    }

    public function resolve(string $castIdentifier): CastInterface
    {
        if ($caster = $this->cache[$castIdentifier] ?? null) {
            return $caster;
        }

        $caster = $this->castResolver->resolve($castIdentifier);

        $this->cache[$castIdentifier] = $caster;

        return $caster;
    }
}
