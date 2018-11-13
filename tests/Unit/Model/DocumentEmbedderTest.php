<?php
namespace Mongolid\Model;

use Mockery as m;
use Mockery\Matcher\Any;
use MongoDB\BSON\ObjectId;
use Mongolid\TestCase;
use stdClass;

class DocumentEmbedderTest extends TestCase
{
    /**
     * @dataProvider getEmbedOptions
     */
    public function testShouldEmbed($originalField, $entity, $method, $expectation)
    {
        // Arrange
        $parent = new stdClass();
        $parent->foo = $originalField;
        $embedder = new DocumentEmbedder();

        // Assert
        $embedder->$method($parent, 'foo', $entity);

        $result = $parent->foo;
        foreach ($expectation as $index => $expectedDoc) {
            if ($expectedDoc instanceof ObjectId) {
                $this->assertEquals($expectedDoc, $result[$index]);

                continue;
            }

            $expectedDocArray = (array) $expectedDoc;
            $resultDocArray = (array) $result[$index];
            foreach ($expectedDocArray as $field => $value) {
                if ($value instanceof Any) {
                    $this->assertTrue(isset($resultDocArray[$field]));
                } else {
                    $this->assertEquals($value, $resultDocArray[$field]);
                }
            }
        }
    }

    public function getEmbedOptions()
    {
        return [
            // ------------------------------
            'embedding array without _id' => [
                'originalField' => null,
                'entity' => [
                    'name' => 'John Doe',
                ],
                'method' => 'embed',
                'expectation' => [
                    ['_id' => m::any(), 'name' => 'John Doe'],
                ],
            ],

            // ------------------------------
            'embedding array with _id' => [
                'originalField' => [],
                'entity' => [
                    '_id' => (new ObjectId('507f191e810c19729de860ea')),
                    'name' => 'John Doe',
                ],
                'method' => 'embed',
                'expectation' => [
                    ['_id' => (new ObjectId('507f191e810c19729de860ea')), 'name' => 'John Doe'],
                ],
            ],

            // ------------------------------
            'embedding object without _id' => [
                'originalField' => null,
                'entity' => (object) [
                    'name' => 'John Doe',
                ],
                'method' => 'embed',
                'expectation' => [
                    (object) ['_id' => m::any(), 'name' => 'John Doe'],
                ],
            ],

            // ------------------------------
            'embedding object with _id' => [
                'originalField' => null,
                'entity' => (object) [
                    '_id' => (new ObjectId('507f191e810c19729de860ea')),
                    'name' => 'John Doe',
                ],
                'method' => 'embed',
                'expectation' => [
                    (object) ['_id' => (new ObjectId('507f191e810c19729de860ea')), 'name' => 'John Doe'],
                ],
            ],

            // ------------------------------
            'updating embedded object with _id' => [
                'originalField' => [
                    [
                        '_id' => (new ObjectId('507f191e810c19729de860ea')),
                        'name' => 'Bob',
                    ],
                ],
                'entity' => (object) [
                    '_id' => (new ObjectId('507f191e810c19729de860ea')),
                    'name' => 'John Doe',
                ],
                'method' => 'embed',
                'expectation' => [
                    (object) ['_id' => (new ObjectId('507f191e810c19729de860ea')), 'name' => 'John Doe'],
                ],
            ],

            // ------------------------------
            'updating embedded array with _id' => [
                'originalField' => [
                    [
                        '_id' => (new ObjectId()),
                        'name' => 'Louis',
                    ],
                    [
                        '_id' => (new ObjectId('507f191e810c19729de860ea')),
                        'name' => 'Bob',
                    ],
                ],
                'entity' => [
                    '_id' => (new ObjectId('507f191e810c19729de860ea')),
                    'name' => 'John Doe',
                ],
                'method' => 'embed',
                'expectation' => [
                    [
                        '_id' => m::any(),
                        'name' => 'Louis',
                    ],
                    [
                        '_id' => (new ObjectId('507f191e810c19729de860ea')),
                        'name' => 'John Doe',
                    ],
                ],
            ],

            // ------------------------------
            'unembeding array with _id' => [
                'originalField' => [
                    [
                        '_id' => (new ObjectId('507f191e810c19729de860ea')),
                        'name' => 'John Doe',
                    ],
                    [
                        '_id' => (new ObjectId()),
                        'name' => 'Louis',
                    ],
                ],
                'entity' => [
                    '_id' => (new ObjectId('507f191e810c19729de860ea')),
                    'name' => 'John Doe',
                ],
                'method' => 'unembed',
                'expectation' => [
                    [
                        '_id' => m::any(),
                        'name' => 'Louis',
                    ],
                ],
            ],

            // ------------------------------
            'attaching array with _id' => [
                'originalField' => null,
                'entity' => [
                    '_id' => (new ObjectId('507f191e810c19729de860ea')),
                    'name' => 'John Doe',
                ],
                'method' => 'attach',
                'expectation' => [
                    (new ObjectId('507f191e810c19729de860ea')),
                ],
            ],

            // ------------------------------
            'attaching object with _id' => [
                'originalField' => null,
                'entity' => (object) [
                    '_id' => (new ObjectId('507f191e810c19729de860ea')),
                    'name' => 'John Doe',
                ],
                'method' => 'attach',
                'expectation' => [
                    (new ObjectId('507f191e810c19729de860ea')),
                ],
            ],

            // ------------------------------
            'attaching object with _id that is already attached' => [
                'originalField' => [
                    (new ObjectId('507f191e810c19729de860ea')),
                    (new ObjectId('507f191e810c19729de86011')),
                ],
                'entity' => (object) [
                    '_id' => (new ObjectId('507f191e810c19729de860ea')),
                    'name' => 'John Doe',
                ],
                'method' => 'attach',
                'expectation' => [
                    (new ObjectId('507f191e810c19729de860ea')),
                    (new ObjectId('507f191e810c19729de86011')),
                ],
            ],

            // ------------------------------
            'attaching object without _id' => [
                'originalField' => null,
                'entity' => (object) [
                    'name' => 'John Doe',
                ],
                'method' => 'attach',
                'expectation' => [],
            ],

            // ------------------------------
            'detaching an object by its _id' => [
                'originalField' => [
                    (new ObjectId('507f191e810c19729de860ea')),
                    (new ObjectId('507f191e810c19729de86011')),
                ],
                'entity' => (object) [
                    '_id' => (new ObjectId('507f191e810c19729de860ea')),
                    'name' => 'John Doe',
                ],
                'method' => 'detach',
                'expectation' => [
                    (new ObjectId('507f191e810c19729de86011')),
                ],
            ],

            // ------------------------------
            'attaching an _id' => [
                'originalField' => null,
                'entity' => new ObjectId('507f191e810c19729de860ea'),
                'method' => 'attach',
                'expectation' => [
                    (new ObjectId('507f191e810c19729de860ea')),
                ],
            ],

            // ------------------------------
            'detaching an object using only the _id when it is an integer' => [
                'originalField' => [
                    6,
                    7,
                ],
                'entity' => 6,
                'method' => 'detach',
                'expectation' => [
                    7,
                ],
            ],

            // ------------------------------
        ];
    }
}
