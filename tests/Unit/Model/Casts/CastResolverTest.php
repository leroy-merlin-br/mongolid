<?php

namespace Mongolid\Model\Casts;

use Mongolid\Model\Casts\DateTime\DateTimeCast;
use Mongolid\Model\Casts\DateTime\ImmutableDateTimeCast;
use Mongolid\Model\Casts\Exceptions\InvalidCastException;
use Mongolid\TestCase;
use Mongolid\Tests\Stubs\Size;

class CastResolverTest extends TestCase
{
    public function testShouldResolveCast(): void
    {
        // Actions
        $dateTimeCast = CastResolver::resolve('datetime');
        $dateTimeImmutableCast = CastResolver::resolve('immutable_datetime');

        // Assertions
        $this->assertInstanceOf(DateTimeCast::class, $dateTimeCast);
        $this->assertInstanceOf(
            ImmutableDateTimeCast::class,
            $dateTimeImmutableCast
        );
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testShouldResolveBackedEnumCast(): void
    {
        // Actions
        $backedEnumCast = CastResolver::resolve(Size::class);

        // Assertions
        $this->assertInstanceOf(BackedEnumCast::class, $backedEnumCast);
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
        $this->expectExceptionMessage('Invalid cast attribute: invalid');

        // Actions
        CastResolver::resolve('invalid');
    }
}
