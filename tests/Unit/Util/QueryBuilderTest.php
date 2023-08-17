<?php

namespace Mongolid\Util;

use Mockery as m;
use MongoDB\BSON\ObjectId;
use Mongolid\Model\ModelInterface;
use Mongolid\TestCase;
use Mongolid\Tests\Stubs\Legacy\ProductWithSoftDelete;

final class QueryBuilderTest extends TestCase
{
    /**
     * @dataProvider queryValueScenarios
     */
    public function testShouldPrepareQueryValue(
        int|array|string $value,
        array $expectation
    ): void {
        // Actions
        $result = QueryBuilder::prepareValueQuery($value);

        // Assertions
        $this->assertMongoQueryEquals($expectation, $result);
    }

    public function queryValueScenarios(): array
    {
        return [
            'An array' => [
                'value' => ['age' => ['$gt' => 25]],
                'expectation' => ['age' => ['$gt' => 25]],
            ],
            'An ObjectId string' => [
                'value' => '507f1f77bcf86cd799439011',
                'expectation' => [
                    '_id' => new ObjectID(
                        '507f1f77bcf86cd799439011'
                    ),
                ],
            ],
            'An ObjectId string within a query' => [
                'value' => ['_id' => '507f1f77bcf86cd799439011'],
                'expectation' => [
                    '_id' => new ObjectID(
                        '507f1f77bcf86cd799439011'
                    ),
                ],
            ],
            'Other type of _id, sequence for example' => [
                'value' => 7,
                'expectation' => ['_id' => 7],
            ],
            'Series of string _ids as the $in parameter' => [
                'value' => ['_id' => ['$in' => ['507f1f77bcf86cd799439011', '507f1f77bcf86cd799439012']]],
                'expectation' => [
                    '_id' => [
                        '$in' => [
                            new ObjectID('507f1f77bcf86cd799439011'),
                            new ObjectID('507f1f77bcf86cd799439012'),
                        ],
                    ],
                ],
            ],
            'Series of string _ids as the $in parameter' => [
                'value' => ['_id' => ['$nin' => ['507f1f77bcf86cd799439011']]],
                'expectation' => [
                    '_id' => [
                        '$nin' => [new ObjectID(
                            '507f1f77bcf86cd799439011'
                        ),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getQuery
     */
    public function testShouldResolveQuery(
        int|array|string $query,
        array $expected
    ): void {
        // Set
        $model = m::mock(ModelInterface::class);

        // Actions
        $actual = QueryBuilder::resolveQuery($query, $model);

        // Assertions
        $this->assertSame($expected, $actual);
    }

    public function getQuery(): array
    {
        return [
            'When query is a string' => [
                'query' => '123',
                'expected' => [
                    '_id' => '123',
                    '$or' => [
                        ['deleted_at' => null],
                        ['deleted_at' => ['$exists' => false]],
                    ],
                ],
            ],
            'When query is a int' => [
                'query' => 123,
                'expected' => [
                    '_id' => 123,
                    '$or' => [
                        ['deleted_at' => null],
                        ['deleted_at' => ['$exists' => false]],
                    ],
                ],
            ],
            'When query have withTrashed field' => [
                'query' => ['_id' => 123,'withTrashed' => true],
                'expected' => [
                    '_id' => 123,
                ],
            ],
        ];
    }

    /**
     * @dataProvider  setDefaultClass
     */
    public function testShouldGetDeleteAtColumn(bool $isDefault, string $expected): void
    {
        // Set
        $model = $this->buildProduct($isDefault);

        // Actions
        $actual = QueryBuilder::getDeletedAtColumn($model);

        // Assertions
        $this->assertSame($expected, $actual);
    }

    public function setDefaultClass(): array
    {
        return [
            'Get class with DELETED_AT default' => [
                'isDefault' => false,
                'expected' => 'custom_deleted_at',
            ],
            'Get class with DELETED_AT custom' => [
                'isDefault' => true,
                'expected' => 'deleted_at',
            ],
        ];
    }

    public function buildProduct(bool $isDefault): ProductWithSoftDelete
    {
        if ($isDefault) {
            return new ProductWithSoftDelete();
        }

        return new class extends ProductWithSoftDelete {
            public const DELETED_AT = 'custom_deleted_at';
        };
    }
}
