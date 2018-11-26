<?php
namespace Mongolid\Query;

use Mockery as m;
use MongoDB\BSON\ObjectId;
use Mongolid\Model\AbstractModel;
use Mongolid\Model\PolymorphableInterface;
use Mongolid\Schema\AbstractSchema;
use Mongolid\TestCase;

class ModelAssemblerTest extends TestCase
{
    /**
     * @dataProvider modelAssemblerFixture
     */
    public function testShouldAssembleModelForTheGivenSchema($inputValue, $availableSchemas, $inputSchema, $expectedOutput)
    {
        // Arrange
        $modelAssembler = new ModelAssembler();
        $schemas = [];
        foreach ($availableSchemas as $key => $value) {
            $schemas[$key] = $this->instance($key, m::mock(AbstractSchema::class.'[]'));
            $schemas[$key]->modelClass = $value['modelClass'];
            $schemas[$key]->fields = $value['fields'];
        }

        // Act
        $result = $modelAssembler->assemble($inputValue, $schemas[$inputSchema]);

        // Assert
        $this->assertEquals($expectedOutput, $result);
    }

    public function modelAssemblerFixture()
    {
        return [
            //---------------------------

            'A simple schema to a model' => [
                'inputValue' => [ // Data that will be used to assembly the model
                    '_id' => new ObjectId('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'grade' => 7.25,
                ],
                'availableSchemas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'modelClass' => StubStudent::class,
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
                'expectedOutput' => new StubStudent([ // Expected output
                    '_id' => new ObjectId('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'grade' => 7.25,
                ]),
            ],

            //---------------------------

            'A schema containing an embedded schema but with null field' => [
                'inputValue' => [ // Data that will be used to assembly the model
                    '_id' => new ObjectId('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'tests' => null,
                    'finalGrade' => 7.25,
                ],
                'availableSchemas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'modelClass' => StubStudent::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'name' => 'string',
                            'age' => 'integer',
                            'tests' => 'schema.TestSchema',
                            'finalGrade' => 'float',
                        ],
                    ],
                    'TestSchema' => [
                        'modelClass' => StubTestGrade::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'subject' => 'string',
                            'grade' => 'float',
                        ],
                    ],
                ],
                'inputSchema' => 'studentSchema', // Schema that will be used to assembly $inputValue
                'expectedOutput' => new StubStudent([ // Expected output
                    '_id' => new ObjectId('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'tests' => null,
                    'finalGrade' => 7.25,
                ]),
            ],

            //---------------------------

            'A stdClass with a schema containing an embedded schema with a document directly into the field' => [
                'inputValue' => (object) [ // Data that will be used to assembly the model
                    '_id' => new ObjectId('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'tests' => [
                        '_id' => new ObjectId('507f1f77bcf86cd7994390ea'),
                        'subject' => 'math',
                        'grade' => 7.25,
                    ],
                    'finalGrade' => 7.25,
                ],
                'availableSchemas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'modelClass' => StubStudent::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'name' => 'string',
                            'age' => 'integer',
                            'tests' => 'schema.TestSchema',
                            'finalGrade' => 'float',
                        ],
                    ],
                    'TestSchema' => [
                        'modelClass' => StubTestGrade::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'subject' => 'string',
                            'grade' => 'float',
                        ],
                    ],
                ],
                'inputSchema' => 'studentSchema', // Schema that will be used to assembly $inputValue
                'expectedOutput' => new StubStudent([ // Expected output
                    '_id' => new ObjectId('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'tests' => [
                        new StubTestGrade([
                            '_id' => new ObjectId('507f1f77bcf86cd7994390ea'),
                            'subject' => 'math',
                            'grade' => 7.25,
                        ]),
                    ],
                    'finalGrade' => 7.25,
                ]),
            ],

            //---------------------------

            'A schema containing an embedded schema with multiple documents in the field' => [
                'inputValue' => [ // Data that will be used to assembly the model
                    '_id' => new ObjectId('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'tests' => [
                        [
                            '_id' => new ObjectId('507f1f77bcf86cd7994390ea'),
                            'subject' => 'math',
                            'grade' => 7.25,
                        ],
                        [
                            '_id' => new ObjectId('507f1f77bcf86cd7994390eb'),
                            'subject' => 'english',
                            'grade' => 9.0,
                        ],
                    ],
                    'finalGrade' => 7.25,
                ],
                'availableSchemas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'modelClass' => StubStudent::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'name' => 'string',
                            'age' => 'integer',
                            'tests' => 'schema.TestSchema',
                            'finalGrade' => 'float',
                        ],
                    ],
                    'TestSchema' => [
                        'modelClass' => StubTestGrade::class,
                        'fields' => [
                            '_id' => 'objectId',
                            'subject' => 'string',
                            'grade' => 'float',
                        ],
                    ],
                ],
                'inputSchema' => 'studentSchema', // Schema that will be used to assembly $inputValue
                'expectedOutput' => new StubStudent([ // Expected output
                    '_id' => new ObjectId('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'tests' => [
                        new StubTestGrade([
                            '_id' => new ObjectId('507f1f77bcf86cd7994390ea'),
                            'subject' => 'math',
                            'grade' => 7.25,
                        ]),
                        new StubTestGrade([
                            '_id' => new ObjectId('507f1f77bcf86cd7994390eb'),
                            'subject' => 'english',
                            'grade' => 9.0,
                        ]),
                    ],
                    'finalGrade' => 7.25,
                ]),
            ],

            //---------------------------

            'A simple schema with a polymorphable interface' => [
                'inputValue' => [ // Data that will be used to assembly the model
                    '_id' => new ObjectId('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'grade' => 7.25,
                ],
                'availableSchemas' => [ // Schemas that will exist in the test context
                    'studentSchema' => [
                        'modelClass' => PolymorphableStudent::class,
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
                'expectedOutput' => new StubStudent([ // Expected output
                    '_id' => new ObjectId('507f1f77bcf86cd799439011'),
                    'name' => 'John Doe',
                    'age' => 25,
                    'grade' => 7.25,
                ]),
            ],
        ];
    }
}

class StubStudent extends AbstractModel
{
    public function __construct($attr = [])
    {
        foreach ($attr as $key => $value) {
            $this->$key = $value;
        }

        $this->syncOriginalDocumentAttributes();
    }
}

class StubTestGrade extends AbstractModel
{
    public function __construct($attr = [])
    {
        foreach ($attr as $key => $value) {
            $this->$key = $value;
        }

        $this->syncOriginalDocumentAttributes();
    }
}

class PolymorphableStudent extends AbstractModel implements PolymorphableInterface
{
    public function polymorph()
    {
        return new StubStudent($this->getDocumentAttributes());
    }
}
