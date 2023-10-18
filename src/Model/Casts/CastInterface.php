<?php

namespace Mongolid\Model\Casts;

use MongoDB\BSON\UTCDateTime;

interface CastInterface
{
    public function get(mixed $value): mixed;

    public function set(mixed $value): mixed;
}
