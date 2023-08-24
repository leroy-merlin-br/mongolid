<?php

namespace Mongolid\Tests\Stubs\Legacy;

use Mongolid\LegacyRecord;

class LegacyRecordStudent extends LegacyRecord
{
    public function __construct($attr = [])
    {
        foreach ($attr as $key => $value) {
            $this->$key = $value;
        }

        $this->original = $this->attributes;
    }
}
