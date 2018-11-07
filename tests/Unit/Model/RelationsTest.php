<?php
namespace Mongolid\Model;

use Mockery as m;
use MongoDB\BSON\ObjectID;
use Mongolid\Cursor\CursorFactory;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Cursor\EmbeddedCursor;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Schema\Schema;
use Mongolid\TestCase;

class RelationsTest extends TestCase
{
    /**
     * @dataProvider referenceScenarios
     */
    public function testShouldReferenceOne($entity, $field, $fieldValue, $useCache, $expectedQuery)
    {
        // Set
        $expectedQuery = $expectedQuery['referencesOne'];
        $model = m::mock(ActiveRecord::class.'[]');
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class)->makePartial());
        $result = new class() extends Schema {
        };

        $model->$field = $fieldValue;

        $this->instance(get_class($entity), $entity);

        // Act
        $dataMapper->expects()
            ->first(m::type('array'), [], $useCache)
            ->andReturnUsing(function ($query) use ($result, $expectedQuery) {
                $this->assertMongoQueryEquals($expectedQuery, $query);

                return $result;
            });

        // Assert
        $this->assertSame(
            $result,
            $this->callProtected($model, 'referencesOne', [get_class($entity), $field, $useCache])
        );
    }

    /**
     * @dataProvider referenceScenarios
     */
    public function testShouldReferenceMany($entity, $field, $fieldValue, $useCache, $expectedQuery)
    {
        // Set
        $expectedQuery = $expectedQuery['referencesMany'];
        $model = m::mock(ActiveRecord::class.'[]');
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class)->makePartial());
        $result = m::mock(CursorInterface::class);

        $model->$field = $fieldValue;

        // Act
        $this->instance(get_class($entity), $entity);

        $dataMapper->expects()
            ->where(m::type('array'), [], $useCache)
            ->andReturnUsing(function ($query) use ($result, $expectedQuery) {
                $this->assertMongoQueryEquals($expectedQuery, $query);

                return $result;
            });

        // Assert
        $this->assertSame(
            $result,
            $this->callProtected($model, 'referencesMany', [get_class($entity), $field, $useCache])
        );
    }

    /**
     * @dataProvider embedsScenarios
     */
    public function testShouldEmbedsOne($entity, $field, $fieldValue, $expectedItems)
    {
        // Set
        $model = m::mock(ActiveRecord::class.'[]');
        $cursorFactory = $this->instance(CursorFactory::class, m::mock(CursorFactory::class));
        $cursor = m::mock(EmbeddedCursor::class);
        $document = $fieldValue;
        $model->$field = $document;

        $instantiableClass = $entity instanceof Schema ? 'stdClass' : get_class($entity);

        // Act
        $cursorFactory->expects()
            ->createEmbeddedCursor($instantiableClass, $expectedItems)
            ->andReturn($cursor);

        $cursor->expects()
            ->first()
            ->andReturn(new $instantiableClass());

        // Assert
        $result = $this->callProtected($model, 'embedsOne', [get_class($entity), $field]);
        $this->assertInstanceOf($instantiableClass, $result);
    }

    /**
     * @dataProvider embedsScenarios
     */
    public function testShouldEmbedsMany($entity, $field, $fieldValue, $expectedItems)
    {
        // Set
        $model = m::mock(ActiveRecord::class.'[]');
        $cursorFactory = $this->instance(CursorFactory::class, m::mock(CursorFactory::class));
        $cursor = m::mock(EmbeddedCursor::class);
        $document = $fieldValue;
        $model->$field = $document;

        $instantiableClass = $entity instanceof Schema ? 'stdClass' : get_class($entity);

        // Act
        $cursorFactory->expects()
            ->createEmbeddedCursor($instantiableClass, $expectedItems)
            ->andReturn($cursor);

        // Assert
        $result = $this->callProtected($model, 'embedsMany', [get_class($entity), $field]);
        $this->assertEquals($cursor, $result);
    }

    /**
     * @dataProvider manipulativeMethods
     */
    public function testShouldEmbeddedUnembedAttachAndDetachDocuments($method)
    {
        // Set
        $model = new class() {
            use Relations;
        };
        $document = m::mock();
        $documentEmbedder = $this->instance(DocumentEmbedder::class, m::mock(DocumentEmbedder::class));

        // Act
        $documentEmbedder->expects()
            ->$method($model, 'foo', $document);

        // Assert
        $model->$method('foo', $document);
    }

    public function referenceScenarios()
    {
        return [
            // -------------------------
            'Schema referenced by numeric id' => [
                'entity' => new class() extends Schema {
                },
                'field' => 'foo',
                'fieldValue' => 12345,
                'useCache' => true,
                'expectedQuery' => [
                    'referencesOne' => ['_id' => 12345],
                    'referencesMany' => ['_id' => ['$in' => [12345]]],
                ],
            ],
            // -------------------------
            'ActiveRecord referenced by string id' => [
                'entity' => new class() extends ActiveRecord {
                    /**
                     * @var {inheritdoc}
                     */
                    protected $collection = 'foobar';
                },
                'field' => 'foo',
                'fieldValue' => 'abc123',
                'useCache' => true,
                'expectedQuery' => [
                    'referencesOne' => ['_id' => 'abc123'],
                    'referencesMany' => ['_id' => ['$in' => ['abc123']]],
                ],
            ],
            // -------------------------
            'Schema referenced by string objectId' => [
                'entity' => new class() extends Schema {
                },
                'field' => 'foo',
                'fieldValue' => ['553e3c80293fce6572ff2a40', '5571df31cf3fce544481a085'],
                'useCache' => false,
                'expectedQuery' => [
                    'referencesOne' => ['_id' => '553e3c80293fce6572ff2a40'],
                    'referencesMany' => ['_id' => ['$in' => [new ObjectID('553e3c80293fce6572ff2a40'), new ObjectID('5571df31cf3fce544481a085')]]],
                ],
            ],
            // -------------------------
            'ActiveRecord referenced by objectId' => [
                'entity' => new class() extends ActiveRecord {
                    /**
                     * @var {inheritdoc}
                     */
                    protected $collection = 'foobar';
                },
                'field' => 'foo',
                'fieldValue' => '577afb0b4d3cec136058fa82',
                'useCache' => true,
                'expectedQuery' => [
                    'referencesOne' => ['_id' => '577afb0b4d3cec136058fa82'],
                    'referencesMany' => ['_id' => ['$in' => ['577afb0b4d3cec136058fa82']]],
                ],
            ],
            // -------------------------
            'Schema referenced with series of numeric ids' => [
                'entity' => new class() extends Schema {
                },
                'field' => 'foo',
                'fieldValue' => [1, 2, 3, 4, 5],
                'useCache' => false,
                'expectedQuery' => [
                    'referencesOne' => ['_id' => 1],
                    'referencesMany' => ['_id' => ['$in' => [1, 2, 3, 4, 5]]],
                ],
            ],
            // -------------------------
            'ActiveRecord referenced with series of string objectIds' => [
                'entity' => new class() extends ActiveRecord {
                    /**
                     * @var {inheritdoc}
                     */
                    protected $collection = 'foobar';
                },
                'field' => 'foo',
                'fieldValue' => ['577afb0b4d3cec136058fa82', '577afb7e4d3cec136258fa83'],
                'useCache' => false,
                'expectedQuery' => [
                    'referencesOne' => ['_id' => '577afb0b4d3cec136058fa82'],
                    'referencesMany' => ['_id' => ['$in' => [new ObjectID('577afb0b4d3cec136058fa82'), new ObjectID('577afb7e4d3cec136258fa83')]]],
                ],
            ],
            // -------------------------
            'Schema referenced with series of real objectIds' => [
                'entity' => new class() extends Schema {
                },
                'field' => 'foo',
                'fieldValue' => [new ObjectID('577afb0b4d3cec136058fa82'), new ObjectID('577afb7e4d3cec136258fa83')],
                'useCache' => true,
                'expectedQuery' => [
                    'referencesOne' => ['_id' => new ObjectID('577afb0b4d3cec136058fa82')],
                    'referencesMany' => ['_id' => ['$in' => [new ObjectID('577afb0b4d3cec136058fa82'), new ObjectID('577afb7e4d3cec136258fa83')]]],
                ],
            ],
            // -------------------------
            'ActiveRecord referenced with null' => [
                'entity' => new class() extends ActiveRecord {
                    /**
                     * @var {inheritdoc}
                     */
                    protected $collection = 'foobar';
                },
                'field' => 'foo',
                'fieldValue' => null,
                'useCache' => false,
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
            // -------------------------
            'Embedded document referent to an Schema' => [
                'entity' => new class() extends Schema {
                },
                'field' => 'foo',
                'fieldValue' => ['_id' => 12345, 'name' => 'batata'],
                'expectedItems' => [['_id' => 12345, 'name' => 'batata']],
            ],
            // -------------------------
            'Embedded documents referent to an Schema' => [
                'entity' => new class() extends Schema {
                },
                'field' => 'foo',
                'fieldValue' => [['_id' => 12345, 'name' => 'batata'], ['_id' => 67890, 'name' => 'bar']],
                'expectedItems' => [['_id' => 12345, 'name' => 'batata'], ['_id' => 67890, 'name' => 'bar']],
            ],
            // -------------------------
            'Embedded document referent to an ActiveRecord entity' => [
                'entity' => new class() extends ActiveRecord {
                    /**
                     * @var {inheritdoc}
                     */
                    protected $collection = 'foobar';
                },
                'field' => 'foo',
                'fieldValue' => ['_id' => 12345, 'name' => 'batata'],
                'expectedItems' => [['_id' => 12345, 'name' => 'batata']],
            ],
            // -------------------------
            'Embedded documents referent to an ActiveRecord entity' => [
                'entity' => new class() extends ActiveRecord {
                    /**
                     * @var {inheritdoc}
                     */
                    protected $collection = 'foobar';
                },
                'field' => 'foo',
                'fieldValue' => [['_id' => 12345, 'name' => 'batata'], ['_id' => 67890, 'name' => 'bar']],
                'expectedItems' => [['_id' => 12345, 'name' => 'batata'], ['_id' => 67890, 'name' => 'bar']],
            ],
            // -------------------------
        ];
    }

    public function manipulativeMethods()
    {
        return [
            ['embed'],
            ['unembed'],
            ['attach'],
            ['detach'],
        ];
    }
}
