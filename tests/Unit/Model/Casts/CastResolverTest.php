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
        $customCasting = CastResolver::resolve(DateTimeCast::class);

        // Assertions
        $this->assertInstanceOf(DateTimeCast::class, $dateTimeCast);
        $this->assertInstanceOf(
            ImmutableDateTimeCast::class,
            $dateTimeImmutableCast
        );
        $this->assertInstanceOf(DateTimeCast::class, $customCasting);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testShouldResolveBackedEnumCast(): void
    {
        // Actions
        $backedEnumCast = CastResolver::resolve(Size::class);
        $customCasting = CastResolver::resolve(BackedEnumCast::class . ':' . Size::class);

        // Assertions
        $this->assertInstanceOf(BackedEnumCast::class, $backedEnumCast);
        $this->assertInstanceOf(BackedEnumCast::class, $customCasting);

    }

    public function testShouldResolveFromCache(): void
    {
        $realCastResolver = \Mockery::mock(CastResolverInterface::class);
        $cacheCastResolver = \Mockery::mock(CastResolverCache::class, [$realCastResolver]);
        $caster = \Mockery::mock(CastInterface::class);
        $this->instance(CastResolverInterface::class, $cacheCastResolver);

        // Expectations
        $realCastResolver->expects()
            ->resolve('datetime')
            ->andReturn($caster)
            ->once();

        $cacheCastResolver->expects()
            ->resolve('datetime')
            ->passthru()
            ->twice();

        // Actions
        $dateTimeCast = CastResolver::resolve('datetime');
        $secondDateTimeCast = CastResolver::resolve('datetime');

        // Assertions
        $this->assertSame($caster, $dateTimeCast);
        $this->assertSame($caster, $secondDateTimeCast);
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
