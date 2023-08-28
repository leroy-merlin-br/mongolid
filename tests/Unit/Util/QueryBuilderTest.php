<?php

namespace Mongolid\Util;

use Mockery as m;
use MongoDB\BSON\ObjectId;
use Mongolid\Model\ModelInterface;
use Mongolid\Query\Resolver;
use Mongolid\TestCase;
use Mongolid\Tests\Stubs\Legacy\ProductWithSoftDelete;

final class QueryBuilderTest extends TestCase
{
    /**
     * @dataProvider queryValueScenarios
     */
    public function testShouldPrepareQueryValue(
        mixed $query,
        bool $isSoftDeleteEnabled,
        array $expectation
    ): void {
        // Actions
        $model = m::mock(ModelInterface::class);
        $model->isSoftDeleteEnabled = $isSoftDeleteEnabled;
        $result = Resolver::resolveQuery($query, $model, false);

        // Assertions
        $this->assertMongoQueryEquals($expectation, $result);
    }

    public function queryValueScenarios(): array
    {
        return [
            'An array' => [
                'query' => ['age' => ['$gt' => 25]],
                'isSoftDeleteEnabled' => false,
                'expectation' => ['age' => ['$gt' => 25]],
            ],
            'An ObjectId string' => [
                'query' => '507f1f77bcf86cd799439011',
                'isSoftDeleteEnabled' => false,
                'expectation' => [
                    '_id' => new ObjectId(
                        '507f1f77bcf86cd799439011'
                    ),
                ],
            ],
            'An ObjectId string within a query' => [
                'query' => ['_id' => '507f1f77bcf86cd799439011'],
                'isSoftDeleteEnabled' => false,
                'expectation' => [
                    '_id' => new ObjectId(
                        '507f1f77bcf86cd799439011'
                    ),
                ],
            ],
            'Series of string _ids as the $in parameter' => [
                'query' => ['_id' => ['$in' => ['507f1f77bcf86cd799439011', '507f1f77bcf86cd799439012']]],
                'isSoftDeleteEnabled' => false,
                'expectation' => [
                    '_id' => [
                        '$in' => [
                            new ObjectId('507f1f77bcf86cd799439011'),
                            new ObjectId('507f1f77bcf86cd799439012'),
                        ],
                    ],
                ],
            ],
            'Series of string _ids as the $nin parameter' => [
                'query' => ['_id' => ['$nin' => ['507f1f77bcf86cd799439011']]],
                'isSoftDeleteEnabled' => false,
                'expectation' => [
                    '_id' => [
                        '$nin' => [new ObjectId(
                            '507f1f77bcf86cd799439011'
                        ),
                        ],
                    ],
                ],
            ],
            'When query is a string and softDelete is enabled' => [
                'query' => '123',
                'isSoftDeleteEnabled' => true,
                'expected' => [
                    '_id' => '123',
                    'deleted_at' => ['$exists' => false],
                ],
            ],
            'When query is a string and softDelete is disabled' => [
                'query' => '123',
                'isSoftDeleteEnabled' => false,
                'expected' => [
                    '_id' => '123',
                ],
            ],
            'When query is a int and softDelete is enabled' => [
                'query' => 123,
                'isSoftDeleteEnabled' => true,
                'expected' => [
                    '_id' => 123,
                    'deleted_at' => ['$exists' => false],
                ],
            ],
            'When query is a int and softDelete is disabled' => [
                'query' => 123,
                'isSoftDeleteEnabled' => false,
                'expected' => [
                    '_id' => 123,
                ],
            ],
            'When query is a objectId and softDelete is enabled' => [
                'query' => $objectId,
                'isSoftDeleteEnabled' => true,
                'expected' => [
                    '_id' => $objectId,
                    'deleted_at' => ['$exists' => false],
                ],
            ],
            'When query is a objectId and softDelete is disabled' => [
                'query' => $objectId,
                'isSoftDeleteEnabled' => false,
                'expected' => [
                    '_id' => $objectId,
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
        $actual = Resolver::getDeletedAtColumn($model);

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
