<?php

namespace Mongolid\Model;

use DateTime;
use Mockery as m;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Cursor\CursorInterface;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Query\Builder;
use Mongolid\Schema\DynamicSchema;
use Mongolid\TestCase;
use Mongolid\Tests\Stubs\Legacy\ProductWithSoftDelete as LegacyProductWithSoftDelete;
use Mongolid\Tests\Stubs\Legacy\Product as LegacyProduct;
use Mongolid\Tests\Stubs\Product;
use Mongolid\Tests\Stubs\ProductWithSoftDelete;

class SoftDeletesTraitTest extends TestCase
{
    /**
     * @dataProvider getSoftDeleteStatus
     */
    public function testShouldReturnStatusOfSoftDelete(
        ?UTCDateTime $date,
        bool $expected,
        bool $isFillable = true
    ): void {
        // Set
        $product = new LegacyProductWithSoftDelete();

        if ($isFillable) {
            $product->deleted_at = $date;
        }

        // Actions
        $actual = $product->isTrashed();

        // Assertions
        $this->assertSame($expected, $actual);
    }

    public function getSoftDeleteStatus(): array
    {
        return [
            'When deleted_at field is filled' => [
                'deletedAt' => new UTCDateTime(new DateTime('today')),
                'expected' => true,
            ],
            'When deleted_at field is null' => [
                'deletedAt' => null,
                'expected' => false,
            ],
            'When there is not an deleted_at field' => [
                'deletedAt' => null,
                'expected' => false,
                'isFillable ' => false,
            ],
        ];
    }

    public function testShouldRestoreProduct(): void
    {
        // Set
        $product = new LegacyProductWithSoftDelete();
        $product->deleted_at = new UTCDateTime(new DateTime('today'));

        $dataMapper = $this->instance(
            DataMapper::class,
            m::mock(DataMapper::class)
        );

        // Expectations
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->update(m::type(LegacyProduct::class), m::type('array'))
            ->andReturnTrue();

        // Actions
        $actual = $product->restore();

        // Assertions
        $this->assertTrue($actual);
    }

    public function testShouldNotRestoreProduct(): void
    {
        // Set
        $product = new LegacyProductWithSoftDelete();

        // Actions
        $actual = $product->restore();

        // Assertions
        $this->assertFalse($actual);
    }

    public function testShouldForceDeleteForLegacyModel(): void
    {
        // Set
        $product = new LegacyProductWithSoftDelete();

        $dataMapper = $this->instance(
            DataMapper::class,
            m::mock(DataMapper::class)
        );

        // Expectations
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->delete(m::type(LegacyProduct::class), m::type('array'))
            ->andReturnTrue();

        // Actions
        $actual = $product->forceDelete();

        // Assertions
        $this->assertTrue($actual);
    }

    public function testShouldExecuteSoftDeleteForLegacyModel(): void
    {
        // Set
        $product = new LegacyProductWithSoftDelete();

        $dataMapper = $this->instance(
            DataMapper::class,
            m::mock(DataMapper::class)
        );

        // Expectations
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->update(m::type(LegacyProduct::class), m::type('array'))
            ->andReturnTrue();

        // Actions
        $actual = $product->delete();

        // Assertions
        $this->assertTrue($actual);
    }

    public function testShouldNotExecuteSoftForLegacyModel(): void
    {
        // Set
        $product = new LegacyProduct();

        $dataMapper = $this->instance(
            DataMapper::class,
            m::mock(DataMapper::class)
        );

        // Expectations
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->delete(m::type(LegacyProduct::class), m::type('array'))
            ->andReturnTrue();

        // Actions
        $actual = $product->delete();

        // Assertions
        $this->assertTrue($actual);
    }

    public function testShouldFindWithTrashedForLegacyModel(): void
    {
        // Set
        $product = new LegacyProductWithSoftDelete();
        $cursor = m::mock(CursorInterface::class);

        $dataMapper = $this->instance(
            DataMapper::class,
            m::mock(DataMapper::class)
        );

        // Expectations
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->where('123', [], false)
            ->andReturn($cursor);

        $dataMapper->expects()
            ->withoutSoftDelete()
            ->andReturnSelf();

        // Actions
        $actual = $product->withTrashed('123');

        // Assertions
        $this->assertInstanceOf(CursorInterface::class, $actual);
    }

    public function testShouldForceDeleteForModel(): void
    {
        // Set
        $product = new ProductWithSoftDelete();

        $builder = $this->instance(
            Builder::class,
            m::mock(Builder::class)
        );

        // Expectations
        $builder->expects()
            ->delete(m::type(Product::class), m::type('array'))
            ->andReturnTrue();

        // Actions
        $actual = $product->forceDelete();

        // Assertions
        $this->assertTrue($actual);
    }

    public function testShouldExecuteSoftDeleteForModel(): void
    {
        // Set
        $product = new ProductWithSoftDelete();

        $builder = $this->instance(
            Builder::class,
            m::mock(Builder::class)
        );

        // Expectations
        $builder->expects()
            ->update(m::type(Product::class), m::type('array'))
            ->andReturnTrue();

        // Actions
        $actual = $product->delete();

        // Assertions
        $this->assertTrue($actual);
    }

    public function testShouldNotExecuteSoftForModel(): void
    {
        // Set
        $product = new Product();

        $builder = $this->instance(
            Builder::class,
            m::mock(Builder::class)
        );

        // Expectations
        $builder->expects()
            ->delete(m::type(Product::class), m::type('array'))
            ->andReturnTrue();

        // Actions
        $actual = $product->delete();

        // Assertions
        $this->assertTrue($actual);
    }

    public function testShouldFindWithTrashedForModel(): void
    {
        // Set
        $product = new ProductWithSoftDelete();
        $cursor = m::mock(CursorInterface::class);

        $builder = $this->instance(
            Builder::class,
            m::mock(Builder::class)
        );

        // Expectations
        $builder->expects()
            ->where(m::type(ProductWithSoftDelete::class), '123', [], false)
            ->andReturn($cursor);

        $builder->expects()
            ->withoutSoftDelete()
            ->andReturnSelf();

        // Actions
        $actual = $product->withTrashed('123');

        // Assertions
        $this->assertInstanceOf(CursorInterface::class, $actual);
    }
}
