<?php

namespace Mongolid\Model;

use Mockery as m;
use MongoDB\BSON\ObjectId;
use Mongolid\TestCase;
use Mongolid\Tests\Stubs\Price;
use Mongolid\Tests\Stubs\Legacy\Product;
use Mongolid\Util\CacheComponent;
use Mongolid\Util\CacheComponentInterface;

final class HasLegacyRelationTraitTest extends TestCase
{
    /**
     * @dataProvider referencesOneScenarios
     */
    public function testReferenceOneShouldNotHitCache($fieldValue, array $expectedQuery): void
    {
        // Set
        $model = new Product();
        $model->_id = $fieldValue;
        $priceModel = $this->instance(
            Price::class,
            m::mock(Price::class)->makePartial()
        );
        $cacheComponent = $this->instance(
            CacheComponentInterface::class,
            m::mock(CacheComponent::class)->makePartial()
        );

        // Expectations
        $cacheComponent->expects()
            ->get("prices:$fieldValue")
            ->andReturnNull();

        $priceModel->expects('first')
            ->with($expectedQuery, [], true)
            ->andReturnSelf();

        // Actions
        $result = $model->price();

        // Assertions
        $this->assertSame($priceModel, $result);
    }

    /**
     * @dataProvider referencesOneScenarios
     */
    public function testReferenceOneShouldNotHitDatabase($fieldValue, array $expectedQuery): void
    {
        // Set
        $model = new Product();
        $model->_id = $fieldValue;
        $expected = new Price();
        $builder = $this->instance(
            Price::class,
            m::mock(Price::class)->makePartial()
        );
        $cacheComponent = $this->instance(
            CacheComponentInterface::class,
            m::mock(CacheComponent::class)->makePartial()
        );

        // Expectations
        $cacheComponent->expects()
            ->get("prices:$fieldValue")
            ->andReturn($expected);

        $builder->expects('first')
            ->with($expectedQuery, [], true)
            ->never();

        // Actions
        $result = $model->price();

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function referencesOneScenarios(): array
    {
        return [
            'referenced by string id' => [
                'fieldValue' => 'abc123',
                'expectedQuery' => ['_id' => 'abc123'],
            ],
            'referenced by objectId represented as string' => [
                'fieldValue' => '577afb0b4d3cec136058fa82',
                'expectedQuery' => [
                    '_id' => new ObjectId(
                        '577afb0b4d3cec136058fa82'
                    ),
                ],
            ],
            'referenced by an objectId itself' => [
                'fieldValue' => new ObjectId('577afb0b4d3cec136058fa82'),
                'expectedQuery' => [
                    '_id' => new ObjectId(
                        '577afb0b4d3cec136058fa82'
                    ),
                ],
            ],
        ];
    }
}
