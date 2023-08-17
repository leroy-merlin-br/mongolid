<?php

namespace Mongolid\Model;

use DateTime;
use Mockery as m;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Cursor\CursorInterface;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Schema\DynamicSchema;
use Mongolid\TestCase;
use Mongolid\Tests\Stubs\Legacy\ProductWithSoftDelete;
use Mongolid\Tests\Stubs\Legacy\Product;

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
        $product = new ProductWithSoftDelete();

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

    public function testShouldforceDeleteProduct(): void
    {
        // Set
        $product = new ProductWithSoftDelete();

        $dataMapper = $this->instance(
            DataMapper::class,
            m::mock(DataMapper::class)
        );

        // Expectations
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->delete(m::type(Product::class), m::type('array'))
            ->andReturnTrue();

        // Actions
        $actual = $product->forceDelete();

        // Assertions
        $this->assertTrue($actual);
    }

    public function testShouldFindWithTrashedProducts(): void
    {
        // Set
        $product = new ProductWithSoftDelete();
        $cursor = m::mock(CursorInterface::class);

        $dataMapper = $this->instance(
            DataMapper::class,
            m::mock(DataMapper::class)
        );

        // Expectations
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->where(['_id' => '123', 'withTrashed' => true], [], false)
            ->andReturn($cursor);

        // Actions
        $actual = $product->withTrashed('123');

        // Assertions
        $this->assertInstanceOf(CursorInterface::class, $actual);
    }
}
