<?php

namespace Mongolid\Model\Casts;

use MongoDB\BSON\UTCDateTime;

interface CastInterface
{
    public static function get(mixed $value): mixed;

    public static function set(mixed $value): mixed;
}
