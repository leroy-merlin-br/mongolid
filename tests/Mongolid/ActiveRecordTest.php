<?php

namespace Mongolid;

use BadMethodCallException;
use Mockery as m;
use MongoDB\BSON\ObjectID;
use MongoDB\Driver\WriteConcern;
use Mongolid\Container\Ioc;
use Mongolid\Model\Attributes;
use Mongolid\Model\Relations;
use Mongolid\Schema\Schema;
use stdClass;
use TestCase;

class ActiveRecordTest extends TestCase
{
    /**
     * @var ActiveRecord
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->entity = new class() extends ActiveRecord {
        };
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();
        m::close();
        unset($this->entity);
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
        $this->assertEquals(
            [Attributes::class, Relations::class],
            array_keys(class_uses(ActiveRecord::class))
        );
    }

    public function testShouldSave()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getDataMapper,getCollectionName,syncOriginalAttributes]');
        $dataMapper = m::mock();

        // Act
        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $entity->shouldReceive('getCollectionName')
            ->andReturn('mongolid');

        $entity->shouldReceive('syncOriginalAttributes')
            ->once();

        $dataMapper->shouldReceive('save')
            ->once()
            ->with($entity, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assert
        $this->assertTrue($entity->save());
    }

    public function testShouldInsert()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getDataMapper,getCollectionName,syncOriginalAttributes]');
        $dataMapper = m::mock();

        // Act
        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $entity->shouldReceive('getCollectionName')
            ->andReturn('mongolid');

        $entity->shouldReceive('syncOriginalAttributes')
            ->once();

        $dataMapper->shouldReceive('insert')
            ->once()
            ->with($entity, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assert
        $this->assertTrue($entity->insert());
    }

    public function testShouldUpdate()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getDataMapper,getCollectionName,syncOriginalAttributes]');
        $dataMapper = m::mock();

        // Act
        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $entity->shouldReceive('getCollectionName')
            ->andReturn('mongolid');

        $entity->shouldReceive('syncOriginalAttributes')
            ->once();

        $dataMapper->shouldReceive('update')
            ->once()
            ->with($entity, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assert
        $this->assertTrue($entity->update());
    }

    public function testShouldDelete()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getDataMapper,getCollectionName]');
        $dataMapper = m::mock();

        // Act
        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $entity->shouldReceive('getCollectionName')
            ->andReturn('mongolid');

        $dataMapper->shouldReceive('delete')
            ->once()
            ->with($entity, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        // Assert
        $this->assertTrue($entity->delete());
    }

    public function testSaveShouldReturnFalseIfCollectionIsNull()
    {
        $this->assertFalse($this->entity->save());
    }

    public function testUpdateShouldReturnFalseIfCollectionIsNull()
    {
        $this->assertFalse($this->entity->update());
    }

    public function testInsertShouldReturnFalseIfCollectionIsNull()
    {
        $this->assertFalse($this->entity->insert());
    }

    public function testDeleteShouldReturnFalseIfCollectionIsNull()
    {
        $this->assertFalse($this->entity->delete());
    }

    public function testShouldGetWithWhereQuery()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getDataMapper]');
        $this->setProtected($entity, 'collection', 'mongolid');
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];
        $dataMapper = m::mock();
        $cursor = m::mock();

        // Act
        Ioc::instance(get_class($entity), $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('where')
            ->once()
            ->with($query, $projection, true)
            ->andReturn($cursor);

        // Assert
        $this->assertEquals($cursor, $entity->where($query, $projection, true));
    }

    public function testShouldGetAll()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getDataMapper]');
        $this->setProtected($entity, 'collection', 'mongolid');
        $dataMapper = m::mock();
        $cursor = m::mock();

        // Act
        Ioc::instance(get_class($entity), $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('all')
            ->once()
            ->andReturn($cursor);

        // Assert
        $this->assertEquals($cursor, $entity->all());
    }

    public function testShouldGetFirstWithQuery()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getDataMapper]');
        $this->setProtected($entity, 'collection', 'mongolid');
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];
        $dataMapper = m::mock();

        // Act
        Ioc::instance(get_class($entity), $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('first')
            ->once()
            ->with($query, $projection, true)
            ->andReturn($entity);

        // Assert
        $this->assertEquals($entity, $entity->first($query, $projection, true));
    }

    public function testShouldGetFirstOrFail()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getDataMapper]');
        $this->setProtected($entity, 'collection', 'mongolid');
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];
        $dataMapper = m::mock();

        // Act
        Ioc::instance(get_class($entity), $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('firstOrFail')
            ->once()
            ->with($query, $projection, true)
            ->andReturn($entity);

        // Assert
        $this->assertEquals($entity, $entity->firstOrFail($query, $projection, true));
    }

    public function testShouldGetFirstOrNewAndReturnExistingModel()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getDataMapper]');
        $this->setProtected($entity, 'collection', 'mongolid');
        $id = 123;
        $dataMapper = m::mock();

        // Act
        Ioc::instance(get_class($entity), $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('first')
            ->once()
            ->with($id)
            ->andReturn($entity);

        // Assert
        $this->assertEquals($entity, $entity->firstOrNew($id));
    }

    public function testShouldGetFirstOrNewAndReturnNewModel()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getDataMapper]');
        $this->setProtected($entity, 'collection', 'mongolid');
        $id = 123;
        $dataMapper = m::mock();

        // Act
        Ioc::instance(get_class($entity), $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('first')
            ->once()
            ->with($id)
            ->andReturn(null);

        // Assert
        $this->assertNotEquals($entity, $entity->firstOrNew($id));
    }

    public function testShouldGetSchemaIfFieldsIsTheClassName()
    {
        // Arrage
        $this->setProtected($this->entity, 'fields', 'MySchemaClass');
        $schema = m::mock(Schema::class);

        // Act
        Ioc::instance('MySchemaClass', $schema);

        // Assert
        $this->assertEquals(
            $schema,
            $this->entity->getSchema()
        );
    }

    public function testShouldGetSchemaIfFieldsDescribesSchemaFields()
    {
        // Arrage
        $fields = ['name' => 'string', 'age' => 'int'];
        $this->setProtected($this->entity, 'fields', $fields);

        // Assert
        $result = $this->entity->getSchema();
        $this->assertInstanceOf(Schema::class, $result);
        $this->assertEquals($fields, $result->fields);
        $this->assertEquals($this->entity->dynamic, $result->dynamic);
        $this->assertEquals($this->entity->getCollectionName(), $result->collection);
        $this->assertEquals(get_class($this->entity), $result->entityClass);
    }

    public function testShouldGetDataMapper()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getSchema]');
        $schema = m::mock(Schema::class.'[]');

        // Act
        $entity->shouldAllowMockingProtectedMethods();

        $entity->shouldReceive('getSchema')
            ->once()
            ->andReturn($schema);

        // Assert
        $result = $this->callProtected($entity, 'getDataMapper');
        $this->assertInstanceOf(DataMapper\DataMapper::class, $result);
        $this->assertEquals($schema, $result->getSchema());
    }

    /**
     * @expectedException \Mongolid\Exception\NoCollectionNameException
     */
    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallAllFunction()
    {
        $entity = new class() extends ActiveRecord {
        };

        $this->assertNull($entity->getCollectionName());

        $entity->all();
    }

    /**
     * @expectedException \Mongolid\Exception\NoCollectionNameException
     */
    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallFirstFunction()
    {
        $entity = new class() extends ActiveRecord {
        };

        $this->assertNull($entity->getCollectionName());

        $entity->first();
    }

    /**
     * @expectedException \Mongolid\Exception\NoCollectionNameException
     */
    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallWhereFunction()
    {
        $entity = new class() extends ActiveRecord {
        };

        $this->assertNull($entity->getCollectionName());

        $entity->where();
    }

    public function testShouldGetCollectionName()
    {
        $entity = new class() extends ActiveRecord {
            protected $collection = 'collection_name';
        };

        $this->assertEquals('collection_name', $entity->getCollectionName());
    }

    public function testShouldAttachToAttribute()
    {
        $entity = new class() extends ActiveRecord {
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

        $this->assertEquals([$embedded->_id], $entity->courseClass);
    }

    public function testShouldEmbedToAttribute()
    {
        $entity = new class() extends ActiveRecord {
            protected $collection = 'collection_name';

            public function classes()
            {
                return $this->embedsMany(stdClass::class, 'courseClasses');
            }
        };
        $embedded = new stdClass();
        $embedded->name = 'Course Class #1';
        $entity->embedToCourseClasses($embedded);

        $this->assertEquals('Course Class #1', $entity->classes()->first()->name);
    }

    public function testShouldThrowBadMethodCallExceptionWhenCallingInvalidMethod()
    {
        $entity = new class() extends ActiveRecord {
            protected $collection = 'collection_name';
        };

        $this->expectException(BadMethodCallException::class);

        $entity->foobar();
    }

    public function testShouldGetSetWriteConcernInActiveRecordClass()
    {
        $this->assertEquals(1, $this->entity->getWriteConcern());
        $this->assertEquals(1, $this->entity->getWriteConcern());
        $this->entity->setWriteConcern(0);
        $this->assertEquals(0, $this->entity->getWriteConcern());
    }
}
