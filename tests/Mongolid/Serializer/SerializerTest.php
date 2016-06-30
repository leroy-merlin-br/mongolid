<?php
namespace Mongolid\Serializer;

use MongoDB\BSON\UTCDateTime as MongoUTCDateTime;
use MongoDB\BSON\ObjectID as MongoObjectID;
use Mongolid\Serializer\Type\ObjectID;
use Mongolid\Serializer\Type\UTCDateTime;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Test case for Serializer class
 */
class SerializerTest extends TestCase
{
    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->serializer = new Serializer();
    }

    public function tearDown()
    {
        parent::tearDown();
        unset($this->serializer);
    }

    /**
     * This test just check if Mongo driver still blocking us to serialize
     * their BSON objects. If this test fails, maybe Serializer namespace should
     * be removed =)
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Serialization of 'MongoDB\BSON\ObjectID' is not allowed
     */
    public function testSerializeMongoObjectShouldTrhowException()
    {
        serialize(['id' => new MongoObjectID()]);
    }

    public function testSerializerShouldSerializeReplacingMongoDBObjects()
    {
        $mongoId   = new MongoObjectID();
        $timestamp = time();
        $mongoDate = new MongoUTCDateTime($timestamp*1000);
        $id        = new ObjectID($mongoId);
        $date      = new UTCDateTime($timestamp);

        $attributes = [
            '_id' => $mongoId,
            'created_at' => $mongoDate,
            'parents' => [$mongoId, $mongoId, $mongoId],
            'comments' => [
                [
                    'author' => 'Jhon',
                    'date' => $mongoDate,
                ],
                [
                    'author' => 'Doe',
                    'date' => $mongoDate,
                    'versions' => [
                        [
                            '_id' => $mongoId,
                            'date' => $mongoDate,
                            'content' => 'Awsome',
                        ],
                        [
                            '_id' => $mongoId,
                            'date' => $mongoDate,
                            'content' => 'Great',
                        ],
                    ]
                ],
            ]
        ];

        $expected = [
            '_id' => $id,
            'created_at' => $date,
            'parents' => [$id, $id, $id],
            'comments' => [
                [
                    'author' => 'Jhon',
                    'date' => $date,
                ],
                [
                    'author' => 'Doe',
                    'date' => $date,
                    'versions' => [
                        [
                            '_id' => $id,
                            'date' => $date,
                            'content' => 'Awsome',
                        ],
                        [
                            '_id' => $id,
                            'date' => $date,
                            'content' => 'Great',
                        ],
                    ]
                ],
            ]
        ];

        $this->assertEquals(
            $expected,
            unserialize($this->serializer->serialize($attributes))
        );
    }

    public function testUnserializeShouldConvertStringToMongoDBObjects()
    {
        $mongoId   = new MongoObjectID();
        $mongoDate = new MongoUTCDateTime(time()*1000);
        $id        = new ObjectID($mongoId);
        $date      = new UTCDateTime($mongoDate);

        $data = serialize([
            '_id' => $id,
            'created_at' => $date,
            'parents' => [$id, $id, $id],
            'comments' => [
                [
                    'author' => 'Jhon',
                    'date' => $date,
                ],
                [
                    'author' => 'Doe',
                    'date' => $date,
                    'versions' => [
                        [
                            '_id' => $id,
                            'date' => $date,
                            'content' => 'Awsome',
                        ],
                        [
                            '_id' => $id,
                            'date' => $date,
                            'content' => 'Great',
                        ],
                    ]
                ],
            ]
        ]);

        $expected = [
            '_id' => $mongoId,
            'created_at' => $mongoDate,
            'parents' => [$mongoId, $mongoId, $mongoId],
            'comments' => [
                [
                    'author' => 'Jhon',
                    'date' => $mongoDate,
                ],
                [
                    'author' => 'Doe',
                    'date' => $mongoDate,
                    'versions' => [
                        [
                            '_id' => $mongoId,
                            'date' => $mongoDate,
                            'content' => 'Awsome',
                        ],
                        [
                            '_id' => $mongoId,
                            'date' => $mongoDate,
                            'content' => 'Great',
                        ],
                    ]
                ],
            ]
        ];

        $this->assertEquals($expected, $this->serializer->unserialize($data));
    }
}
