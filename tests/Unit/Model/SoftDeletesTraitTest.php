<?php

namespace Mongolid\Model;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Schema\DynamicSchema;
use Mongolid\TestCase;
use Mongolid\Tests\Stubs\Product;
use Mockery as m;
use Mongolid\Tests\Stubs\ProductWithSoftDelete;

class SoftDeletesTraitTest extends TestCase
{
    public function testShouldReturnStatusOfSoftDelete(): void
    {
        // Set
        $date = new UTCDateTime(new DateTime('today'));
        $product = new ProductWithSoftDelete();
        $product->deleted_at = $date;

        // Actions
        $actual = $product->isTrashed();

        // Assertions
        $this->assertTrue($actual);
    }

    public function testShouldRestoreProduct(): void
    {
        // Set
        $product = new ProductWithSoftDelete();
        $date = new UTCDateTime(new DateTime('today'));
        $product->deleted_at = $date;
        $dataMapper = $this->instance(
            DataMapper::class,
            m::mock(DataMapper::class)
        );

        // Expectations
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->save(m::type(Product::class), m::type('array'))
            ->andReturnTrue();

        // Actions
        $actual = $product->restore();

        // Assertions
        $this->assertTrue($actual);
    }

    public function testShouldNotRestoreProduct(): void
    {
        // Set
        $product = new ProductWithSoftDelete();

        // Actions
        $actual = $product->restore();

        // Assertions
        $this->assertFalse($actual);
    }
}
