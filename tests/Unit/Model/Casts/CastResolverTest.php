<?php

namespace Mongolid\Model\Casts;

use Mongolid\Model\Casts\DateTime\DateTimeCast;
use Mongolid\Model\Casts\DateTime\ImmutableDateTimeCast;
use Mongolid\Model\Casts\Exceptions\InvalidCastException;
use Mongolid\TestCase;

class CastResolverTest extends TestCase
{
    public function testShouldResolveCast(): void
    {
        // Actions
        $dateTimeCast = CastResolver::resolve('datetime');
        $dateTimeImmutableCast = CastResolver::resolve('immutable_datetime');

        // Assertions
        $this->assertInstanceOf(DateTimeCast::class, $dateTimeCast);
        $this->assertInstanceOf(ImmutableDateTimeCast::class, $dateTimeImmutableCast);
    }

    public function testShouldResolveFromCache(): void
    {
        // Actions
        $dateTimeCast = CastResolver::resolve('datetime');
        $secondDateTimeCast = CastResolver::resolve('datetime');

        // Assertions
        $this->assertInstanceOf(DateTimeCast::class, $dateTimeCast);
        $this->assertInstanceOf(DateTimeCast::class, $secondDateTimeCast);
    }

    public function testShouldThrowExceptionWhenGivenInvalidCastToBeResolved(): void
    {
        // Expectations
        $this->expectException(InvalidCastException::class);
        $this->expectExceptionMessage('Invalid cast attribute: invalid. Use a valid one like datetime,immutable_datetime');

        // Actions
        CastResolver::resolve('invalid');
    }
}
