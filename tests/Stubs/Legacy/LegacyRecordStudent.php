<?php

namespace Mongolid\Tests\Stubs\Legacy;

use Mongolid\LegacyRecord;

class LegacyRecordStudent extends LegacyRecord
{
    /**
     * @param array<string, mixed> $attr
     */
    public function __construct(array $attr = [])
    {
        foreach ($attr as $key => $value) {
            $this->$key = $value;
        }

        $this->original = $this->attributes;
    }
}
