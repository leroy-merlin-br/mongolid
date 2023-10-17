<?php

namespace Mongolid\Model\Casts\DateTime;

use DateTimeImmutable;
use MongoDB\BSON\UTCDateTime;
use Mongolid\TestCase;

class ImmutableDateTimeCastTest extends TestCase
{
    public function testShouldGet(): void
    {
        // Set
        $timestamp = new UTCDateTime(
            DateTimeImmutable::createFromFormat('d/m/Y H:i:s', '08/10/2025 12:30:45')
        );

        // Actions
        $revoked_at = ImmutableDateTimeCast::get(null);
        $birthdate = ImmutableDateTimeCast::get($timestamp);
        $created_at = ImmutableDateTimeCast::get($timestamp);

        // Assertions
        $this->assertNull($revoked_at);
        $this->assertInstanceOf(DateTimeImmutable::class, $birthdate);
        $this->assertInstanceOf(DateTimeImmutable::class, $created_at);
    }
}
