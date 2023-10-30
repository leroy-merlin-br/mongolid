<?php

namespace Mongolid\Model\Casts;

use Mongolid\Model\Casts\Exceptions\InvalidTypeException;
use Mongolid\TestCase;
use Mongolid\Tests\Stubs\Size;
use ValueError;

/**
 * @requires PHP >= 8.1
 */
class BackedEnumCastTest extends TestCase
{
    public function testShouldGetValue(): void
    {
        // Set
        $cast = new BackedEnumCast(Size::class);

        // Actions
        $result = $cast->get('small');

        // Asserts
        $this->assertEquals(Size::Small, $result);
    }

    public function testShouldGetNull(): void
    {
        // Set
        $cast = new BackedEnumCast(Size::class);

        // Actions
        $result = $cast->get(null);

        // Asserts
        $this->assertNull($result);
    }

    public function testShoulThrowErrorWhenGetWithUnknownValue(): void
    {
        // Set
        $cast = new BackedEnumCast(Size::class);

        // Expects
        $this->expectException(ValueError::class);

        // Actions
        $cast->get('unknow value');
    }

    public function testShouldSet(): void
    {
        // Set
        $cast = new BackedEnumCast(Size::class);

        // Actions
        $result = $cast->set(Size::Big);

        // Asserts
        $this->assertEquals('big', $result);
    }

    public function testShouldSetNull(): void
    {
        // Set
        $cast = new BackedEnumCast(Size::class);

        // Actions
        $result = $cast->set(null);

        // Asserts
        $this->assertNull($result);
    }

    public function testShouldThrowErrorWhenSetWithUnknownValue(): void
    {
        // Set
        $cast = new BackedEnumCast(Size::class);

        // Expectations
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage(
            'Value expected type ' . Size::class . '|null, given string'
        );

        // Actions
        $cast->set('unknow value');
    }
}
