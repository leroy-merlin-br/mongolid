<?php

namespace Mongolid;

use BadMethodCallException;
use Mockery as m;
use MongoDB\BSON\ObjectID;
use MongoDB\Driver\WriteConcern;
use Mongolid\Container\Container;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Model\Exception\NoCollectionNameException;
use Mongolid\Model\HasLegacyAttributesTrait;
use Mongolid\Model\HasLegacyRelationsTrait;
use Mongolid\Schema\Schema;
use stdClass;

class LegacyRecordTest extends TestCase
{
    protected LegacyRecord $entity;

    public function setUp(): void
    {
        parent::setUp();
        $this->entity = new class() extends LegacyRecord {
            protected $collection = 'legacy_record';
        };
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
        unset($this->entity);
    }

    public function testShouldImplementModelTraits(): void
    {
        // Assert
        $this->assertEquals(
            [HasLegacyAttributesTrait::class, HasLegacyRelationsTrait::class],
            array_keys(class_uses(LegacyRecord::class))
        );
    }

    public function testShouldSave(): void
    {
        // Arrage
        $entity = m::mock(LegacyRecord::class.'[getDataMapper,getCollectionName,syncOriginalAttributes]');
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

    public function testShouldInsert(): void
    {
        // Arrage
        $entity = m::mock(LegacyRecord::class.'[getDataMapper,getCollectionName,syncOriginalAttributes]');
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

    public function testShouldUpdate(): void
    {
        // Arrage
        $entity = m::mock(LegacyRecord::class.'[getDataMapper,getCollectionName,syncOriginalAttributes]');
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

    public function testShouldDelete(): void
    {
        // Arrage
        $entity = m::mock(LegacyRecord::class.'[getDataMapper,getCollectionName]');
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

    public function testShouldGetWithWhereQuery(): void
    {
        // Arrage
        $entity = m::mock(LegacyRecord::class.'[getDataMapper]');
        $this->setProtected($entity, 'collection', 'mongolid');
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];
        $dataMapper = m::mock();
        $cursor = m::mock(CursorInterface::class);

        // Act
        Container::instance($entity::class, $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('where')
            ->once()
            ->with($query, $projection, true)
            ->andReturn($cursor);

        // Assert
        $this->assertEquals($cursor, $entity->where($query, $projection, true));
    }

    public function testShouldGetAll(): void
    {
        // Arrage
        $entity = m::mock(LegacyRecord::class.'[getDataMapper]');
        $this->setProtected($entity, 'collection', 'mongolid');
        $dataMapper = m::mock();
        $cursor = m::mock(CursorInterface::class);

        // Act
        Container::instance($entity::class, $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('all')
            ->once()
            ->andReturn($cursor);

        // Assert
        $this->assertEquals($cursor, $entity->all());
    }

    public function testShouldGetFirstWithQuery(): void
    {
        // Arrage
        $entity = m::mock(LegacyRecord::class.'[getDataMapper]');
        $this->setProtected($entity, 'collection', 'mongolid');
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];
        $dataMapper = m::mock();

        // Act
        Container::instance($entity::class, $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('first')
            ->once()
            ->with($query, $projection, true)
            ->andReturn($entity);

        // Assert
        $this->assertEquals($entity, $entity->first($query, $projection, true));
    }

    public function testShouldGetFirstOrFail(): void
    {
        // Arrage
        $entity = m::mock(LegacyRecord::class.'[getDataMapper]');
        $this->setProtected($entity, 'collection', 'mongolid');
        $query = ['foo' => 'bar'];
        $projection = ['some', 'fields'];
        $dataMapper = m::mock();

        // Act
        Container::instance($entity::class, $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('firstOrFail')
            ->once()
            ->with($query, $projection, true)
            ->andReturn($entity);

        // Assert
        $this->assertEquals($entity, $entity->firstOrFail($query, $projection, true));
    }

    public function testShouldGetFirstOrNewAndReturnExistingModel(): void
    {
        // Arrage
        $entity = m::mock(LegacyRecord::class.'[getDataMapper]');
        $this->setProtected($entity, 'collection', 'mongolid');
        $id = 123;
        $dataMapper = m::mock();

        // Act
        Container::instance($entity::class, $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('first')
            ->once()
            ->with($id)
            ->andReturn($entity);

        // Assert
        $this->assertEquals($entity, $entity->firstOrNew($id));
    }

    public function testShouldGetFirstOrNewAndReturnNewModel(): void
    {
        // Arrage
        $entity = m::mock(LegacyRecord::class.'[getDataMapper]');
        $this->setProtected($entity, 'collection', 'mongolid');
        $id = 123;
        $dataMapper = m::mock();

        // Act
        Container::instance($entity::class, $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('first')
            ->once()
            ->with($id)
            ->andReturn(null);

        // Assert
        $this->assertNotEquals($entity, $entity->firstOrNew($id));
    }

    public function testShouldGetSchemaIfFieldsIsTheClassName(): void
    {
        // Arrage
        $this->setProtected($this->entity, 'fields', 'MySchemaClass');
        $schema = m::mock(Schema::class);

        // Act
        Container::instance('MySchemaClass', $schema);

        // Assert
        $this->assertEquals(
            $schema,
            $this->entity->getSchema()
        );
    }

    public function testShouldGetSchemaIfFieldsDescribesSchemaFields(): void
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
        $this->assertEquals($this->entity::class, $result->entityClass);
    }

    public function testShouldGetDataMapper(): void
    {
        // Arrange
        $entity = m::mock(LegacyRecord::class.'[getSchema]');
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

    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallAllFunction(): void
    {
        $entity = new class() extends LegacyRecord {
        };

        $this->expectException(NoCollectionNameException::class);

        $this->assertNull($entity->getCollectionName());

        $entity->all();
    }

    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallFirstFunction(): void
    {
        $entity = new class() extends LegacyRecord {
        };

        $this->expectException(NoCollectionNameException::class);

        $this->assertNull($entity->getCollectionName());

        $entity->first();
    }

    public function testShouldRaiseExceptionWhenHasNoCollectionAndTryToCallWhereFunction(): void
    {
        $entity = new class() extends LegacyRecord {
        };

        $this->expectException(NoCollectionNameException::class);

        $this->assertNull($entity->getCollectionName());

        $entity->where();
    }

    public function testShouldGetCollectionName(): void
    {
        $entity = new class() extends LegacyRecord {
            protected $collection = 'collection_name';
        };

        $this->assertEquals('collection_name', $entity->getCollectionName());
    }

    public function testShouldAttachToAttribute(): void
    {
        $entity = new class() extends LegacyRecord {
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

    public function testShouldEmbedToAttribute(): void
    {
        $entity = new class() extends LegacyRecord {
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

    public function testShouldThrowBadMethodCallExceptionWhenCallingInvalidMethod(): void
    {
        $entity = new class() extends LegacyRecord {
            protected $collection = 'collection_name';
        };

        $this->expectException(BadMethodCallException::class);

        $entity->foobar();
    }

    public function testShouldGetSetWriteConcernInLegacyRecordClass(): void
    {
        $this->assertEquals(1, $this->entity->getWriteConcern());
        $this->assertEquals(1, $this->entity->getWriteConcern());
        $this->entity->setWriteConcern(0);
        $this->assertEquals(0, $this->entity->getWriteConcern());
    }

    public function testShouldRefreshModels(): void
    {
        // Set
        $id = 'some-id-value';
        $entity = m::mock(LegacyRecord::class.'[getDataMapper]');
        $this->setProtected($entity, 'collection', 'mongolid');
        $entity->_id = $id;
        $dataMapper = m::mock();
        Container::instance($entity::class, $entity);

        // Expectations
        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('first')
            ->once()
            ->with('some-id-value', [], false)
            ->andReturn($entity);

        // Actions
        $result = $entity->fresh();

        // Assertions
        $this->assertSame($entity, $result);
    }

}
