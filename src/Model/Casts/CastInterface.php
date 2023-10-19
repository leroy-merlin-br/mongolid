<?php

namespace Mongolid\Model\Casts;

interface CastInterface
{
    public function get(mixed $value): mixed;

    public function set(mixed $value): mixed;
}
