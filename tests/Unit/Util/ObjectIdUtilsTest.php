<?php
namespace Mongolid\Util;

use MongoDB\BSON\ObjectId;
use Mongolid\TestCase;

final class ObjectIdUtilsTest extends TestCase
{
    /**
     * @dataProvider objectIdStringScenarios
     */
    public function testShouldEvaluateIfValueIsAnObjectId($value, bool $expectation): void
    {
        // Actions
        $result = ObjectIdUtils::isObjectId($value);

        // Assertions
        $this->assertSame($expectation, $result);
    }

    public function objectIdStringScenarios(): array
    {
        $object = new class {
            public function __toString()
            {
                return '577a68c44d3cec1f6c7796a2';
            }
        };

        return [
            ['value' => '577a68c44d3cec1f6c7796a2', 'expectation' => true],
            ['value' => '577a68d24d3cec1f817796a5', 'expectation' => true],
            ['value' => '577a68d14d3cec1f6d7796a3', 'expectation' => true],
            ['value' => '507f1f77bcf86cd799439011', 'expectation' => true],
            ['value' => '507f191e810c19729de860ea', 'expectation' => true],
            ['value' => $object, 'expectation' => true],
            ['value' => new ObjectId(), 'expectation' => true],
            ['value' => new ObjectId('577a68c44d3cec1f6c7796a2'), 'expectation' => true],
            ['value' => 1, 'expectation' => false],
            ['value' => '507f191e810c197', 'expectation' => false],
            ['value' => 123456, 'expectation' => false],
            ['value' => 'abcdefgh1234567890123456', 'expectation' => false],
            ['value' => '+07f191e810c19729de860ea', 'expectation' => false],
            ['value' => 1234567, 'expectation' => false],
            ['value' => 0.5, 'expectation' => false],
            ['value' => null, 'expectation' => false],
            ['value' => true, 'expectation' => false],
            ['value' => false, 'expectation' => false],
            ['value' => ['key' => 'value'], 'expectation' => false],
            ['value' => ['577a68c44d3cec1f6c7796a2'], 'expectation' => false],
        ];
    }
}
