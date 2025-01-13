<?php

namespace Mongolid\Tests\Integration;

use Mockery as m;
use MongoDB\BSON\ObjectID;
use Mongolid\Container\Container;
use Mongolid\DataMapper\EntityAssembler;
use Mongolid\Schema\Schema;
use Mongolid\TestCase;
use Mongolid\Tests\Stubs\Legacy\LegacyRecordStudent;

class EntityAssemblerTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    /**
     * @dataProvider entityAssemblerFixture
     */
    public function testShouldAssembleEntityForTheGivenSchema(
        $inputValue,
        $availableSchemas,
        $inputSchema,
        $expectedOutput
    ) {
        // Arrange
        $entityAssembler = new EntityAssembler();
        $schemas = [];
        foreach ($availableSchemas as $key => $value) {
            $schemas[$key] = m::mock(Schema::class . '[]');
            $schemas[$key]->entityClass = $value['entityClass'];
            $schemas[$key]->fields = $value['fields'];
        }

        // Act
        foreach ($schemas as $className => $instance) {
            Container::instance($className, $instance);
        }

        // Assert
        $result = $entityAssembler->assemble(
            $inputValue,
            $schemas[$inputSchema]
        );
        $this->assertEquals($expectedOutput, $result);
    }

    public function entityAssemblerFixture()
    {
        return [
            'A simple schema to a entity' => [
                'inputValue' => [ // Data that will be used to assembly the entity
                    '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'grade' => 7.25,
                ],
                'availableSchmas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'entityClass' => LegacyRecordStudent::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'name' => 'string',
                            'age' => 'integer',
                            'grade' => 'float',
                            'finalGrade' => 'float',
                        ],
                    ],
                ],
                'inputSchema' => 'studentSchema', // Schema that will be used to assembly $inputValue
                'expectedOutput' => new LegacyRecordStudent(
                    [ // Expected output
                        '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                        'name' => 'John Doe',
                        'age' => 25,
                        'grade' => 7.25,
                    ]
                ),
            ],

            //---------------------------

            'A schema containing an embeded schema but with null field' => [
                'inputValue' => [ // Data that will be used to assembly the entity
                    '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'tests' => null,
                    'finalGrade' => 7.25,
                ],
                'availableSchmas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'entityClass' => LegacyRecordStudent::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'name' => 'string',
                            'age' => 'integer',
                            'tests' => 'schema.TestSchema',
                            'finalGrade' => 'float',
                        ],
                    ],
                    'TestSchema' => [
                        'entityClass' => LegacyRecordStudent::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'subject' => 'string',
                            'grade' => 'float',
                        ],
                    ],
                ],
                'inputSchema' => 'studentSchema', // Schema that will be used to assembly $inputValue
                'expectedOutput' => new LegacyRecordStudent(
                    [ // Expected output
                        '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                        'name' => 'John Doe',
                        'age' => 25,
                        'tests' => null,
                        'finalGrade' => 7.25,
                    ]
                ),
            ],

            //---------------------------

            'A stdClass with a schema containing an embeded schema with a document directly into the field' => [
                'inputValue' => (object) [ // Data that will be used to assembly the entity
                    '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'tests' => [
                        '_id' => new ObjectID('507f1f77bcf86cd7994390ea'),
                        'subject' => 'math',
                        'grade' => 7.25,
                    ],
                    'finalGrade' => 7.25,
                ],
                'availableSchmas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'entityClass' => LegacyRecordStudent::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'name' => 'string',
                            'age' => 'integer',
                            'tests' => 'schema.TestSchema',
                            'finalGrade' => 'float',
                        ],
                    ],
                    'TestSchema' => [
                        'entityClass' => LegacyRecordStudent::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'subject' => 'string',
                            'grade' => 'float',
                        ],
                    ],
                ],
                'inputSchema' => 'studentSchema', // Schema that will be used to assembly $inputValue
                'expectedOutput' => new LegacyRecordStudent(
                    [ // Expected output
                        '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                        'name' => 'John Doe',
                        'age' => 25,
                        'tests' => [
                            new LegacyRecordStudent([
                                '_id' => new ObjectID(
                                    '507f1f77bcf86cd7994390ea'
                                ),
                                'subject' => 'math',
                                'grade' => 7.25,
                            ]),
                        ],
                        'finalGrade' => 7.25,
                    ]
                ),
            ],

            //---------------------------

            'A schema containing an embeded schema with multiple documents in the field' => [
                'inputValue' => [ // Data that will be used to assembly the entity
                    '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'tests' => [
                        [
                            '_id' => new ObjectID('507f1f77bcf86cd7994390ea'),
                            'subject' => 'math',
                            'grade' => 7.25,
                        ],
                        [
                            '_id' => new ObjectID('507f1f77bcf86cd7994390eb'),
                            'subject' => 'english',
                            'grade' => 9.0,
                        ],
                    ],
                    'finalGrade' => 7.25,
                ],
                'availableSchmas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'entityClass' => LegacyRecordStudent::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'name' => 'string',
                            'age' => 'integer',
                            'tests' => 'schema.TestSchema',
                            'finalGrade' => 'float',
                        ],
                    ],
                    'TestSchema' => [
                        'entityClass' => LegacyRecordStudent::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'subject' => 'string',
                            'grade' => 'float',
                        ],
                    ],
                ],
                'inputSchema' => 'studentSchema', // Schema that will be used to assembly $inputValue
                'expectedOutput' => new LegacyRecordStudent(
                    [ // Expected output
                        '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                        'name' => 'John Doe',
                        'age' => 25,
                        'tests' => [
                            new LegacyRecordStudent([
                                '_id' => new ObjectID(
                                    '507f1f77bcf86cd7994390ea'
                                ),
                                'subject' => 'math',
                                'grade' => 7.25,
                            ]),
                            new LegacyRecordStudent([
                                '_id' => new ObjectID(
                                    '507f1f77bcf86cd7994390eb'
                                ),
                                'subject' => 'english',
                                'grade' => 9.0,
                            ]),
                        ],
                        'finalGrade' => 7.25,
                    ]
                ),
            ],

            //---------------------------

            'A simple schema with a polymorphable interface' => [
                'inputValue' => [ // Data that will be used to assembly the entity
                    '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'grade' => 7.25,
                ],
                'availableSchmas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'entityClass' => LegacyRecordStudent::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'name' => 'string',
                            'age' => 'integer',
                            'grade' => 'float',
                            'finalGrade' => 'float',
                        ],
                    ],
                ],
                'inputSchema' => 'studentSchema', // Schema that will be used to assembly $inputValue
                'expectedOutput' => new LegacyRecordStudent(
                    [ // Expected output
                        '_id' => new ObjectID('507f1f77bcf86cd799439011'),
                        'name' => 'John Doe',
                        'age' => 25,
                        'grade' => 7.25,
                    ]
                ),
            ],
        ];
    }
}
