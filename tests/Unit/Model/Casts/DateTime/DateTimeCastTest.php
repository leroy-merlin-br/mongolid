<?php

namespace Mongolid\Model\Casts\DateTime;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Mongolid\TestCase;

class DateTimeCastTest extends TestCase
{
    public function testShouldGet(): void
    {
        // Set
        $timestamp = new UTCDateTime(
            DateTime::createFromFormat('d/m/Y H:i:s', '08/10/2025 12:30:45')
        );
        $dateTimeCast = new DateTimeCast();

        // Actions
        $revoked_at = $dateTimeCast->get(null);
        $expires_at = $dateTimeCast->get($timestamp);
        $validated_at = $dateTimeCast->get($timestamp);

        // Assertions
        $this->assertNull($revoked_at);
        $this->assertInstanceOf(DateTime::class, $expires_at);
        $this->assertInstanceOf(DateTime::class, $validated_at);
    }
}
