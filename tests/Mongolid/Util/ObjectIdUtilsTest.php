<?php

namespace Mongolid\Util;

use MongoDB\BSON\ObjectID;
use TestCase;

class ObjectIdUtilsTest extends TestCase
{
    /**
     * @dataProvider objectIdStringScenarios
     */
    public function testShouldEvaluateIfValueIsAnObjectid($value, $expectation)
    {
        $this->assertEquals($expectation, ObjectIdUtils::isObjectId($value));
    }

    public function objectIdStringScenarios()
    {
        return [
            // [Value, Expectation],
            ['577a68c44d3cec1f6c7796a2', true],
            ['577a68d24d3cec1f817796a5', true],
            ['577a68d14d3cec1f6d7796a3', true],
            ['507f1f77bcf86cd799439011', true],
            ['507f191e810c19729de860ea', true],
            [new ObjectID(), true],
            ['1', false],
            ['507f191e810c197', false],
            ['123456', false],
            ['abcdefgh1234567890123456', false],
            ['+07f191e810c19729de860ea', false],
            [1234567, false],
            [0.5, false],
            [['key' => 'value'], false],
        ];
    }
}
