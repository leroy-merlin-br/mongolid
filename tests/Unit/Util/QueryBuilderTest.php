<?php

namespace Mongolid\Util;

use MongoDB\BSON\ObjectId;
use Mongolid\TestCase;

final class QueryBuilderTest extends TestCase
{
    /**
     * @dataProvider queryValueScenarios
     */
    public function testShouldPrepareQueryValue($value, $expectation)
    {
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
}
