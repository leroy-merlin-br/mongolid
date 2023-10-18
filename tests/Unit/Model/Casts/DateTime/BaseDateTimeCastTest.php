<?php

namespace Mongolid\Model\Casts\DateTime;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Mongolid\TestCase;

class BaseDateTimeCastTest extends TestCase
{
    public function testShouldSet(): void
    {
        // Set
        $dateInDateTime = DateTime::createFromFormat('d/m/Y H:i:s', '08/10/2025 12:30:45');
        $dateTimeCast = new DateTimeCast();

        // Actions
        $expires_at = $dateTimeCast->set($dateInDateTime);
        $nulled_at = $dateTimeCast->set(null);
        $restored_at = $dateTimeCast->set(
            new UTCDateTime($dateInDateTime)
        );

        // Assertions
        $this->assertInstanceOf(UTCDateTime::class, $expires_at);
        $this->assertInstanceOf(UTCDateTime::class, $restored_at);
        $this->assertNull($nulled_at);
    }
}
