<?php
namespace Mongolid\Model;

use BadMethodCallException;
use Mockery as m;
use MongoDB\BSON\ObjectID;
use MongoDB\Driver\WriteConcern;
use Mongolid\Cursor\CursorInterface;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Exception\NoCollectionNameException;
use Mongolid\Schema\DynamicSchema;
use Mongolid\Schema\Schema;
use Mongolid\TestCase;
use stdClass;

class ActiveRecordTest extends TestCase
{
    /**
     * @var ActiveRecord
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->entity = new class() extends ActiveRecord
        {
            /**
             * {@inheritdoc}
             */
            protected $collection = 'mongolid';

            public function unsetCollection()
            {
                unset($this->collection);
            }

            public function setFields($value)
            {
                $this->fields = $value;
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->entity);
        parent::tearDown();
    }

    public function testShouldHaveCorrectPropertiesByDefault()
    {
        // Assert
        $this->assertAttributeEquals(
            [
                '_id' => 'objectId',
                'created_at' => 'createdAtTimestamp',
                'updated_at' => 'updatedAtTimestamp',
            ],
            'fields',
            $this->entity
        );
        $this->assertTrue($this->entity->dynamic);
    }

    public function testShouldImplementModelTraits()
    {
        // Assert
        $this->assertSame(
            [Attributes::class, Relations::class],
            array_keys(class_uses(ActiveRecord::class))
        );
    }

    public function testShouldSave()
    {
        // Arrage
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        // Act
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->save($this->entity, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assert
        $this->assertTrue($this->entity->save());
    }

    public function testShouldInsert()
    {
        // Arrage
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        // Act
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->insert($this->entity, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assert
        $this->assertTrue($this->entity->insert());
    }

    public function testShouldUpdate()
    {
        // Arrage
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        // Act
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->update($this->entity, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assert
        $this->assertTrue($this->entity->update());
    }

    public function testShouldDelete()
    {
        // Arrage
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        // Act
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->delete($this->entity, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assert
        $this->assertTrue($this->entity->delete());
    }

    public function testSaveShouldReturnFalseIfCollectionIsNull()
    {
        $this->entity->unsetCollection();
        $this->assertFalse($this->entity->save());
    }

    public function testUpdateShouldReturnFalseIfCollectionIsNull()
    {
        $this->entity->unsetCollection();
        $this->assertFalse($this->entity->update());
    }

    public function testInsertShouldReturnFalseIfCollectionIsNull()
    {
        $this->entity->unsetCollection();
        $this->assertFalse($this->entity->insert());
    }

    public function testDeleteShouldReturnFalseIfCollectionIsNull()
    {
        $this->entity->unsetCollection();
        $this->assertFalse($this->entity->delete());
    }

    public function testShouldGetWithWhereQuery()
    {
        // Arrage
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        $cursor = m::mock(CursorInterface::class);

        // Act
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->where($query, $projection, true)
            ->andReturn($cursor);

        // Assert
        $this->assertSame($cursor, $this->entity->where($query, $projection, true));
    }

    public function testShouldGetAll()
    {
        // Arrage
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        $cursor = m::mock(CursorInterface::class);

        // Act
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->all()
            ->andReturn($cursor);

        // Assert
        $this->assertSame($cursor, $this->entity->all());
    }

    public function testShouldGetFirstWithQuery()
    {
        // Arrage
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        // Act
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->first($query, $projection, true)
            ->andReturn($this->entity);

        // Assert
        $this->assertSame($this->entity, $this->entity->first($query, $projection, true));
    }

    public function testShouldGetFirstOrFail()
    {
        // Arrage
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];

        // Act
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->firstOrFail($query, $projection, true)
            ->andReturn($this->entity);

        // Assert
        $this->assertSame($this->entity, $this->entity->firstOrFail($query, $projection, true));
    }

    public function testShouldGetFirstOrNewAndReturnExistingModel()
    {
        // Arrage
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));
        $id = 123;

        // Act
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->first($id)
            ->andReturn($this->entity);

        // Assert
        $this->assertSame($this->entity, $this->entity->firstOrNew($id));
    }

    public function testShouldGetFirstOrNewAndReturnNewModel()
    {
        // Arrage
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));
        $id = 123;

        // Act
        $dataMapper->expects()
            ->setSchema(m::type(DynamicSchema::class));

        $dataMapper->expects()
            ->first($id)
            ->andReturn(null);

        // Assert
        $this->assertNotEquals($this->entity, $this->entity->firstOrNew($id));
    }

    public function testShouldGetSchemaIfFieldsIsTheClassName()
    {
        // Arrage
        $this->entity->setFields('MySchemaClass');
        $schema = $this->instance('MySchemaClass', m::mock(Schema::class));

        // Assert
        $this->assertSame(
            $schema,
            $this->entity->getSchema()
        );
    }

    public function testShouldGetSchemaIfFieldsDescribesSchemaFields()
    {
        // Arrage
        $fields = ['name' => 'string', 'age' => 'int'];
        $this->entity->setFields($fields);

        // Assert
        $result = $this->entity->getSchema();
        $this->assertInstanceOf(Schema::class, $result);
        $this->assertSame($fields, $result->fields);
        $this->assertSame($this->entity->dynamic, $result->dynamic);
        $this->assertSame($this->entity->getCollectionName(), $result->collection);
        $this->assertSame(get_class($this->entity), $result->entityClass);
    }

    public function testShouldGetDataMapper()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getSchema]');
        $schema = m::mock(Schema::class.'[]');

        // Act
        $entity->shouldAllowMockingProtectedMethods();

        $entity->expects()
            ->getSchema()
            ->andReturn($schema);

        // Assert
        $result = $this->callProtected($entity, 'getDataMapper');
        $this->assertInstanceOf(DataMapper::class, $result);
        $this->assertSame($schema, $result->getSchema());
    }

    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallAllFunction()
    {
        $entity = new class() extends ActiveRecord {
        };

        $this->expectException(NoCollectionNameException::class);
        $entity->all();
    }

    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallFirstFunction()
    {
        $entity = new class() extends ActiveRecord {
        };

        $this->expectException(NoCollectionNameException::class);
        $entity->first();
    }

    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallWhereFunction()
    {
        $entity = new class() extends ActiveRecord {
        };

        $this->expectException(NoCollectionNameException::class);
        $entity->where();
    }

    public function testShouldGetCollectionName()
    {
        $this->assertSame('mongolid', $this->entity->getCollectionName());
    }

    public function testShouldAttachToAttribute()
    {
        $entity = new class() extends ActiveRecord
        {
            /**
             * @var {inheritdoc}
             */
            protected $collection = 'collection_name';

            public function class()
            {
                return $this->referencesOne(stdClass::class, 'courseClass');
            }
        };
        $embedded = new stdClass();
        $embedded->_id = new ObjectID();
        $embedded->name = 'Course Class #1';
        $entity->attachToCourseClass($embedded);

        $this->assertSame([$embedded->_id], $entity->courseClass);
    }

    public function testShouldEmbedToAttribute()
    {
        $this->entity = new class() extends ActiveRecord
        {
            /**
             * @var {inheritdoc}
             */
            protected $collection = 'collection_name';

            public function classes()
            {
                return $this->embedsMany(stdClass::class, 'courseClasses');
            }
        };
        $embedded = new stdClass();
        $embedded->name = 'Course Class #1';
        $this->entity->embedToCourseClasses($embedded);

        $this->assertSame('Course Class #1', $this->entity->classes()->first()->name);
    }

    public function testShouldThrowBadMethodCallExceptionWhenCallingInvalidMethod()
    {
        $this->entity = new class() extends ActiveRecord
        {
            /**
             * @var {inheritdoc}
             */
            protected $collection = 'collection_name';
        };

        $this->expectException(BadMethodCallException::class);

        $this->entity->foobar();
    }

    public function testShouldGetSetWriteConcernInActiveRecordClass()
    {
        $this->assertSame(1, $this->entity->getWriteConcern());
        $this->entity->setWriteConcern(0);
        $this->assertSame(0, $this->entity->getWriteConcern());
    }
}
