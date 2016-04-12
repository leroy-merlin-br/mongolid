<?php

namespace Mongolid\DataMapper;

use Mockery as m;
use MongoDB\BSON\ObjectID;
use Mongolid\Container\Ioc;
use Mongolid\Schema;
use TestCase;

class EntityAssemblerTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    /**
     * @dataProvider EntityAssemblerFixture
     */
    public function testShouldAssembleEntityForTheGivenSchema($inputValue, $availableSchmas, $inputSchema, $expectedOutput)
    {
        // Arrange
        $entityAssembler = new EntityAssembler;
        $schemas = [];
        foreach ($availableSchmas as $key => $value) {
            $schemas[$key] = m::mock(Schema::class.'[]');
            $schemas[$key]->entityClass = $value['entityClass'];
            $schemas[$key]->fields      = $value['fields'];
        }

        // Act
        foreach ($schemas as $className => $instance) {
            Ioc::instance($className, $instance);
        }

        // Assert
        $result = $entityAssembler->assemble($inputValue, $schemas[$inputSchema]);
        $this->assertEquals($expectedOutput, $result);
    }

    public function EntityAssemblerFixture()
    {
        return [
            //---------------------------

            'A simple schema to a entity' => [
                'inputValue' => [ // Data that will be used to assembly the entity
                    '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age'  => 25,
                    'grade' => 7.25
                ],
                'availableSchmas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'entityClass' => _stubStudent::class,
                        'fields' => [
                            '_id'        => 'objectId',
                            'name'       => 'string',
                            'age'        => 'integer',
                            'grade'      => 'float',
                            'finalGrade' => 'float',
                        ]
                    ]
                ],
                'inputSchema' => 'studentSchema', // Schema that will be used to assembly $inputValue
                'expectedOutput' => new _stubStudent([ // Expected output
                    '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age'  => 25,
                    'grade' => 7.25
                ])
            ],

            //---------------------------

            'A schema containing an embeded schema but with null field' => [
                'inputValue' => [ // Data that will be used to assembly the entity
                    '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age'  => 25,
                    'tests' => null,
                    'finalGrade' => 7.25
                ],
                'availableSchmas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'entityClass' => _stubStudent::class,
                        'fields' => [
                            '_id'        => 'objectId',
                            'name'       => 'string',
                            'age'        => 'integer',
                            'tests'      => 'schema.TestSchema',
                            'finalGrade' => 'float',
                        ]
                    ],
                    'TestSchema' => [
                        'entityClass' => _stubTestGrade::class,
                        'fields' => [
                            '_id'        => 'objectId',
                            'subject'    => 'string',
                            'grade'      => 'float',
                        ]
                    ]
                ],
                'inputSchema' => 'studentSchema', // Schema that will be used to assembly $inputValue
                'expectedOutput' => new _stubStudent([ // Expected output
                    '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age'  => 25,
                    'tests' => null,
                    'finalGrade' => 7.25
                ])
            ],

            //---------------------------

            'A stdClass with a schema containing an embeded schema with a document directly into the field' => [
                'inputValue' => (object)[ // Data that will be used to assembly the entity
                    '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age'  => 25,
                    'tests' => [
                        '_id' => new ObjectID('507f1f77bcf86cd7994390ea'),
                        'subject' => 'math',
                        'grade'   => 7.25
                    ],
                    'finalGrade' => 7.25
                ],
                'availableSchmas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'entityClass' => _stubStudent::class,
                        'fields' => [
                            '_id'        => 'objectId',
                            'name'       => 'string',
                            'age'        => 'integer',
                            'tests'      => 'schema.TestSchema',
                            'finalGrade' => 'float',
                        ]
                    ],
                    'TestSchema' => [
                        'entityClass' => _stubTestGrade::class,
                        'fields' => [
                            '_id'        => 'objectId',
                            'subject'    => 'string',
                            'grade'      => 'float',
                        ]
                    ]
                ],
                'inputSchema' => 'studentSchema', // Schema that will be used to assembly $inputValue
                'expectedOutput' => new _stubStudent([ // Expected output
                    '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age'  => 25,
                    'tests' => [
                        new _stubTestGrade([
                            '_id' => new ObjectID('507f1f77bcf86cd7994390ea'),
                            'subject' => 'math',
                            'grade'   => 7.25
                        ])
                    ],
                    'finalGrade' => 7.25
                ])
            ],

            //---------------------------

            'A schema containing an embeded schema with multiple documents in the field' => [
                'inputValue' => [ // Data that will be used to assembly the entity
                    '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age'  => 25,
                    'tests' => [
                        [
                            '_id' => new ObjectID('507f1f77bcf86cd7994390ea'),
                            'subject' => 'math',
                            'grade'   => 7.25
                        ],
                        [
                            '_id' => new ObjectID('507f1f77bcf86cd7994390eb'),
                            'subject' => 'english',
                            'grade'   => 9.0
                        ],
                    ],
                    'finalGrade' => 7.25
                ],
                'availableSchmas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'entityClass' => _stubStudent::class,
                        'fields' => [
                            '_id'        => 'objectId',
                            'name'       => 'string',
                            'age'        => 'integer',
                            'tests'      => 'schema.TestSchema',
                            'finalGrade' => 'float',
                        ]
                    ],
                    'TestSchema' => [
                        'entityClass' => _stubTestGrade::class,
                        'fields' => [
                            '_id'        => 'objectId',
                            'subject'    => 'string',
                            'grade'      => 'float',
                        ]
                    ]
                ],
                'inputSchema' => 'studentSchema', // Schema that will be used to assembly $inputValue
                'expectedOutput' => new _stubStudent([ // Expected output
                    '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age'  => 25,
                    'tests' => [
                        new _stubTestGrade([
                            '_id' => new ObjectID('507f1f77bcf86cd7994390ea'),
                            'subject' => 'math',
                            'grade'   => 7.25
                        ]),
                        new _stubTestGrade([
                            '_id' => new ObjectID('507f1f77bcf86cd7994390eb'),
                            'subject' => 'english',
                            'grade'   => 9.0
                        ])
                    ],
                    'finalGrade' => 7.25
                ])
            ],

            //---------------------------
        ];
    }
}

class _stubStudent extends \stdClass {
    public function __construct($attr = []) {
        foreach ($attr as $key => $value) {
            $this->$key = $value;
        }
    }
}

class _stubTestGrade extends \stdClass {
    public function __construct($attr = []) {
        foreach ($attr as $key => $value) {
            $this->$key = $value;
        }
    }
}
