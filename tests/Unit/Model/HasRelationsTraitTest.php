<?php
namespace Mongolid\Model;

use Mockery as m;
use MongoDB\BSON\ObjectId;
use Mongolid\Cursor\EmbeddedCursor;
use Mongolid\DataMapper\DataMapper;
use Mongolid\TestCase;

class HasRelationsTraitTest extends TestCase
{
    /**
     * @dataProvider referenceScenarios
     */
    public function testShouldReferenceOne($fieldValue, $expectedQuery)
    {
        // Set
        $model = new UserStub();
        $model->refOne = $fieldValue;

        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class)->makePartial());
        $expectedQuery = $expectedQuery['referencesOne'];
        $expected = new RelatedStub();

        // Expectations
        $dataMapper->expects()
            ->first($expectedQuery, [], true)
            ->andReturn($expected);

        // Actions
        $result = $model->relationReferencesOne;

        // Assertions
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider referenceScenarios
     */
    public function testShouldReferenceMany($fieldValue, $expectedQuery)
    {
        // Set
        $model = new UserStub();
        $model->refMany = $fieldValue;

        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class)->makePartial());
        $expectedQuery = $expectedQuery['referencesMany'];
        $expected = new EmbeddedCursor(RelatedStub::class, []);

        // Expectations
        $dataMapper->expects()
            ->where($expectedQuery, [], true)
            ->andReturn($expected);

        // Actions
        $result = $model->relationReferencesMany;

        // Assertions
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider embedsScenarios
     */
    public function testShouldEmbedsOne($fieldValue, $expectedItems)
    {
        // Set
        $model = new UserStub();
        $model->embOne = $fieldValue;

        // Act
        $result = $model->relationEmbedsOne;
        $values = array_map(
            function ($item) {
                return $item->getDocumentAttributes();
            },
            $result->all()
        );

        // Assert
        $this->assertInstanceOf(EmbeddedCursor::class, $result);
        $this->assertContainsOnlyInstancesOf(RelatedStub::class, $result->all());
        $this->assertEquals($expectedItems, $values);
    }

    /**
     * @dataProvider embedsScenarios
     */
    public function testShouldEmbedsMany($fieldValue, $expectedItems)
    {
        // Set
        $model = new UserStub();
        $model->embMany = $fieldValue;

        // Act
        $result = $model->relationEmbedsMany;
        $values = array_map(
            function ($item) {
                return $item->getDocumentAttributes();
            },
            $result->all()
        );

        // Assert
        $this->assertInstanceOf(EmbeddedCursor::class, $result);
        $this->assertContainsOnlyInstancesOf(RelatedStub::class, $result->all());
        $this->assertEquals($expectedItems, $values);
    }

    public function referenceScenarios()
    {
        return [
            'ActiveRecord referenced by string id' => [
                'fieldValue' => 'abc123',
                'expectedQuery' => [
                    'referencesOne' => ['_id' => 'abc123'],
                    'referencesMany' => ['_id' => ['$in' => ['abc123']]],
                ],
            ],
            'ActiveRecord referenced by objectId' => [
                'fieldValue' => '577afb0b4d3cec136058fa82',
                'expectedQuery' => [
                    'referencesOne' => ['_id' => new ObjectId('577afb0b4d3cec136058fa82')],
                    'referencesMany' => ['_id' => ['$in' => [new ObjectId('577afb0b4d3cec136058fa82')]]],
                ],
            ],
            'ActiveRecord referenced with series of string objectIds' => [
                'fieldValue' => [new ObjectId('577afb0b4d3cec136058fa82'), new ObjectId('577afb7e4d3cec136258fa83')],
                'expectedQuery' => [
                    'referencesOne' => ['_id' => new ObjectId('577afb0b4d3cec136058fa82')],
                    'referencesMany' => [
                        '_id' => [
                            '$in' => [
                                new ObjectId('577afb0b4d3cec136058fa82'),
                                new ObjectId('577afb7e4d3cec136258fa83'),
                            ],
                        ],
                    ],
                ],
            ],
            // TODO should not hit database?
            'ActiveRecord referenced with null' => [
                'fieldValue' => null,
                'expectedQuery' => [
                    'referencesOne' => ['_id' => null],
                    'referencesMany' => ['_id' => ['$in' => []]],
                ],
            ],
        ];
    }

    public function embedsScenarios()
    {
        return [
            'Embedded document referent to an Schema' => [
                'fieldValue' => ['_id' => 12345, 'name' => 'batata'],
                'expectedItems' => [['_id' => 12345, 'name' => 'batata']],
            ],
            'Embedded documents referent to an Schema' => [
                'fieldValue' => [['_id' => 12345, 'name' => 'batata'], ['_id' => 67890, 'name' => 'bar']],
                'expectedItems' => [['_id' => 12345, 'name' => 'batata'], ['_id' => 67890, 'name' => 'bar']],
            ],
            'Embedded document referent to an ActiveRecord entity' => [
                'fieldValue' => ['_id' => 12345, 'name' => 'batata'],
                'expectedItems' => [['_id' => 12345, 'name' => 'batata']],
            ],
            'Embedded documents referent to an ActiveRecord entity' => [
                'fieldValue' => [['_id' => 12345, 'name' => 'batata'], ['_id' => 67890, 'name' => 'bar']],
                'expectedItems' => [['_id' => 12345, 'name' => 'batata'], ['_id' => 67890, 'name' => 'bar']],
            ],
        ];
    }
}

class UserStub extends AbstractActiveRecord
{
    /**
     * {@inheritdoc}
     */
    protected $collection = 'users';

    public function relationReferencesOne()
    {
        return $this->referencesOne(RelatedStub::class, 'refOne');
    }

    public function relationReferencesMany()
    {
        return $this->referencesMany(RelatedStub::class, 'refMany');
    }

    public function relationEmbedsOne()
    {
        return $this->embedsOne(RelatedStub::class, 'embOne');
    }

    public function relationEmbedsMany()
    {
        return $this->embedsMany(RelatedStub::class, 'embMany');
    }
}

class RelatedStub extends AbstractActiveRecord
{
    /**
     * {@inheritdoc}
     */
    protected $collection = 'related';
}
