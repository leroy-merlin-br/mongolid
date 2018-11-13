<?php
namespace Mongolid\Model;

use Mockery as m;
use Mockery\Matcher\Any;
use MongoDB\BSON\ObjectId;
use Mongolid\Connection\Connection;
use Mongolid\DataMapper\DataMapper;
use Mongolid\TestCase;

class DocumentEmbedderTest extends TestCase
{
    /**
     * @dataProvider getEmbedOptions
     */
    public function testShouldEmbed($originalField, $entityFields, $method, $expectation)
    {
        // Arrange
        $connection = new Connection();
        $parent = new DataMapper($connection);
        $parent->foo = $originalField;

        if (is_array($entityFields)) {
            $entity = new class extends AbstractActiveRecord
            {
            };
            $entity->fill($entityFields);
        } else {
            $entity = $entityFields;
        }
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
            'embedding object without _id' => [
                'originalField' => null,
                'entity' => [
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
                'entity' => [
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
                'entity' => [
                    '_id' => (new ObjectId('507f191e810c19729de860ea')),
                    'name' => 'John Doe',
                ],
                'method' => 'embed',
                'expectation' => [
                    (object) ['_id' => (new ObjectId('507f191e810c19729de860ea')), 'name' => 'John Doe'],
                ],
            ],

            // ------------------------------
            'attaching object with _id' => [
                'originalField' => null,
                'entity' => [
                    '_id' => new ObjectId('507f191e810c19729de860ea'),
                    'name' => 'John Doe',
                ],
                'method' => 'attach',
                'expectation' => [
                    new ObjectId('507f191e810c19729de860ea'),
                ],
            ],

            // ------------------------------
            'attaching object with _id that is already attached' => [
                'originalField' => [
                    new ObjectId('507f191e810c19729de860ea'),
                    new ObjectId('507f191e810c19729de86011'),
                ],
                'entity' => [
                    '_id' => new ObjectId('507f191e810c19729de860ea'),
                    'name' => 'John Doe',
                ],
                'method' => 'attach',
                'expectation' => [
                    new ObjectId('507f191e810c19729de860ea'),
                    new ObjectId('507f191e810c19729de86011'),
                ],
            ],

            // ------------------------------
            'attaching object without _id' => [
                'originalField' => null,
                'entity' => [
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
                'entity' => [
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
