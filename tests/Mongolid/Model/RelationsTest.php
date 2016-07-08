<?php
namespace Mongolid\Model;

use Mockery as m;
use MongoDB\BSON\ObjectID;
use Mongolid\ActiveRecord;
use Mongolid\Container\Ioc;
use Mongolid\Cursor\Cursor;
use Mongolid\Cursor\EmbeddedCursor;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Model\DocumentEmbedder;
use Mongolid\Schema;
use TestCase;

class RelationsTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    /**
     * @dataProvider referenceScenarios
     */
    public function testShouldReferenceOne($entity, $field, $fieldValue, $expectedQuery)
    {
        // Set
        $expectedQuery = $expectedQuery['referencesOne'];
        $model         = m::mock(ActiveRecord::class.'[]');
        $dataMapper    = m::mock(DataMapper::class);
        $result        = m::mock();

        $model->$field = $fieldValue;

        // Act
        Ioc::instance(DataMapper::class, $dataMapper);
        Ioc::instance('EntityClass', $entity);

        $dataMapper->shouldReceive('first')
            ->once()
            ->andReturnUsing(function ($query) use ($result, $expectedQuery) {
                $this->assertMongoQueryEquals($expectedQuery, $query);
                return $result;
            }) ;

        // Assert
        $this->assertSame(
            $result,
            $this->callProtected($model, 'referencesOne', ['EntityClass', $field])
        );
    }

    /**
     * @dataProvider referenceScenarios
     */
    public function testShouldReferenceMany($entity, $field, $fieldValue, $expectedQuery)
    {
        // Set
        $expectedQuery = $expectedQuery['referencesMany'];
        $model         = m::mock(ActiveRecord::class.'[]');
        $dataMapper    = m::mock(DataMapper::class);
        $result        = m::mock(Cursor::class);

        $model->$field = $fieldValue;

        // Act
        Ioc::instance(DataMapper::class, $dataMapper);
        Ioc::instance('EntityClass', $entity);

        $dataMapper->shouldReceive('where')
            ->once()
            ->andReturnUsing(function ($query) use ($result, $expectedQuery) {
                $this->assertMongoQueryEquals($expectedQuery, $query);
                return $result;
            }) ;

        // Assert
        $this->assertSame(
            $result,
            $this->callProtected($model, 'referencesMany', ['EntityClass', $field])
        );
    }

    public function testShouldEmbedsOne()
    {
        // Set
        $model  = m::mock(ActiveRecord::class.'[]');
        $document = ['_id' => 12345, 'name' => 'batata'];
        $model->foo = [$document];

        // Assert
        $result = $this->callProtected($model, 'embedsOne', ['stdClass', 'foo']);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals($document, (array) $result);
    }

    public function testShouldEmbedsMany()
    {
        // Set
        $model  = m::mock(ActiveRecord::class.'[]');
        $documents = [
            ['_id' => 1, 'name' => 'batata'],
            ['_id' => 2, 'name' => 'foobar']
        ];
        $model->foo = $documents;

        // Assert
        $cursor = $this->callProtected($model, 'embedsMany', ['stdClass', 'foo']);
        $this->assertInstanceOf(EmbeddedCursor::class, $cursor);
        $this->assertAttributeEquals($documents, 'items', $cursor);
        $this->assertAttributeEquals('stdClass', 'entityClass', $cursor);
    }

    /**
     * @dataProvider manipulativeMethods
     */
    public function testShouldEmbededUnembedAttachAndDetachDocuments($method)
    {
        // Set
        $model = new class {
            use Relations;
        };
        $document         = m::mock();
        $documentEmbedder = m::mock(DocumentEmbedder::class);

        // Act
        Ioc::instance(DocumentEmbedder::class, $documentEmbedder);

        $documentEmbedder->shouldReceive($method)
            ->once()
            ->with($model, 'foo', $document);

        // Assert
        $model->$method('foo', $document);
    }

    public function referenceScenarios()
    {
        return [
            // -------------------------
            'Schema referenced by numeric id' => [
                'entity' => new class extends Schema {},
                'field' => 'foo',
                'fieldValue' => 12345,
                'expectedQuery' => [
                    'referencesOne' => ['_id' => 12345],
                    'referencesMany' => ['_id' => ['$in' => [12345]]]
                ]
            ],
            // -------------------------
            'ActiveRecord referenced by string id' => [
                'entity' => new class extends ActiveRecord { protected $collection = 'foobar'; },
                'field' => 'foo',
                'fieldValue' => 'abc123',
                'expectedQuery' => [
                    'referencesOne' => ['_id' => 'abc123'],
                    'referencesMany' => ['_id' => ['$in' => ['abc123']]]
                ]
            ],
            // -------------------------
            'ActiveRecord referenced by objectId' => [
                'entity' => new class extends ActiveRecord { protected $collection = 'foobar'; },
                'field' => 'foo',
                'fieldValue' => '577afb0b4d3cec136058fa82',
                'expectedQuery' => [
                    'referencesOne' => ['_id' => '577afb0b4d3cec136058fa82'],
                    'referencesMany' => ['_id' => ['$in' => ['577afb0b4d3cec136058fa82']]]
                ]
            ],
            // -------------------------
            'Schema referenced with series of numeric ids' => [
                'entity' => new class extends Schema {},
                'field' => 'foo',
                'fieldValue' => [1, 2, 3, 4, 5],
                'expectedQuery' => [
                    'referencesOne' => ['_id' => 1],
                    'referencesMany' => ['_id' => ['$in' => [1, 2, 3, 4, 5]]]
                ]
            ],
            // -------------------------
            'ActiveRecord referenced with series of string objectIds' => [
                'entity' => new class extends ActiveRecord { protected $collection = 'foobar'; },
                'field' => 'foo',
                'fieldValue' => ['577afb0b4d3cec136058fa82', '577afb7e4d3cec136258fa83'],
                'expectedQuery' => [
                    'referencesOne' => ['_id' => '577afb0b4d3cec136058fa82'],
                    'referencesMany' => ['_id' => ['$in' => ['577afb0b4d3cec136058fa82', '577afb7e4d3cec136258fa83']]]
                ]
            ],
            // -------------------------
            'Schema referenced with series of real objectIds' => [
                'entity' => new class extends Schema {},
                'field' => 'foo',
                'fieldValue' => [new ObjectID('577afb0b4d3cec136058fa82'), new ObjectID('577afb7e4d3cec136258fa83')],
                'expectedQuery' => [
                    'referencesOne' => ['_id' => new ObjectID('577afb0b4d3cec136058fa82')],
                    'referencesMany' => ['_id' => ['$in' => [new ObjectID('577afb0b4d3cec136058fa82'), new ObjectID('577afb7e4d3cec136258fa83')]]]
                ]
            ],
            // -------------------------
            'ActiveRecord referenced with null' => [
                'entity' => new class extends ActiveRecord { protected $collection = 'foobar'; },
                'field' => 'foo',
                'fieldValue' => null,
                'expectedQuery' => [
                    'referencesOne' => ['_id' => null],
                    'referencesMany' => ['_id' => ['$in' => []]]
                ]
            ],
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
