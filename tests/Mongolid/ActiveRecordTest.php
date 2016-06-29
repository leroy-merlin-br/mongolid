<?php
namespace Mongolid;

use Mockery as m;
use Mongolid\Container\Ioc;
use Mongolid\Model\Attributes;
use Mongolid\Model\Relations;
use Mongolid\Schema;
use Mongolid\Serializer\Serializer;
use Serializable;
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
        $this->entity = new class extends ActiveRecord {
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

    public function testActiveRecordShouldBeSerializable()
    {
        $this->assertInstanceOf(Serializable::class, $this->entity);
    }

    public function testShouldHaveCorrectPropertiesByDefault()
    {
        // Assert
        $this->assertAttributeEquals(
            [
                '_id' => 'objectId',
                'created_at' => 'createdAtTimestamp',
                'updated_at' => 'updatedAtTimestamp'
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
        $entity = m::mock(ActiveRecord::class.'[getDataMapper,getCollectionName]');
        $dataMapper = m::mock();

        // Act
        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $entity->shouldReceive('getCollectionName')
            ->andReturn('mongolid');

        $dataMapper->shouldReceive('save')
            ->once()
            ->with($entity)
            ->andReturn(true);

        // Assert
        $this->assertTrue($entity->save());
    }

    public function testShouldInsert()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getDataMapper,getCollectionName]');
        $dataMapper = m::mock();

        // Act
        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $entity->shouldReceive('getCollectionName')
            ->andReturn('mongolid');

        $dataMapper->shouldReceive('insert')
            ->once()
            ->with($entity)
            ->andReturn(true);

        // Assert
        $this->assertTrue($entity->insert());
    }

    public function testShouldUpdate()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getDataMapper,getCollectionName]');
        $dataMapper = m::mock();

        // Act
        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $entity->shouldReceive('getCollectionName')
            ->andReturn('mongolid');

        $dataMapper->shouldReceive('update')
            ->once()
            ->with($entity)
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
            ->with($entity)
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
        $query      = ['foo' => 'bar'];
        $dataMapper = m::mock();
        $cursor     = m::mock();

        // Act
        Ioc::instance(get_class($entity), $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('where')
            ->once()
            ->with($query, true)
            ->andReturn($cursor);

        // Assert
        $this->assertEquals($cursor, $entity->where($query, true));
    }

    public function testShouldGetAll()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[getDataMapper]');
        $dataMapper = m::mock();
        $cursor     = m::mock();

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
        $query      = ['foo' => 'bar'];
        $dataMapper = m::mock();
        $cursor     = m::mock();

        // Act
        Ioc::instance(get_class($entity), $entity);

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('first')
            ->once()
            ->with($query, true)
            ->andReturn($cursor);

        // Assert
        $this->assertEquals($cursor, $entity->first($query, true));
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
            $this->callProtected($this->entity, 'getSchema')
        );
    }

    public function testShouldGetSchemaIfFieldsDescribesSchemaFields()
    {
        // Arrage
        $fields = ['name' => 'string', 'age' => 'int'];
        $this->setProtected($this->entity, 'fields', $fields);

        // Assert
        $result = $this->callProtected($this->entity, 'getSchema');
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
        $this->assertEquals($schema, $result->schema);
    }

    public function testShouldGetCollectionName()
    {
        $entity = new class extends ActiveRecord {
            protected $collection = 'collection_name';
        };

        $this->assertEquals($entity->getCollectionName(), 'collection_name');
    }

    public function testSerializeShouldCallSerializerAndReturnString()
    {
        $serializer = m::mock(Serializer::class);
        $attributes = ['some', 'attributes'];
        $this->entity->fill($attributes);

        $serializer->shouldReceive('serialize')
            ->with($attributes)
            ->once()
            ->andReturn('some-serialized-string');

        Ioc::instance(Serializer::class, $serializer);

        $this->assertEquals(
            'some-serialized-string',
            $this->entity->serialize()
        );
    }

    public function testUnderializeShouldCallSerializerAndFillObjectSuccessfully()
    {
        $serializer = m::mock(Serializer::class);
        $attributes = ['some' => 'attributes'];

        $serializer->shouldReceive('unserialize')
            ->with('some-serialized-string')
            ->once()
            ->andReturn($attributes);

        Ioc::instance(Serializer::class, $serializer);

        $this->entity->unserialize('some-serialized-string');

        $this->assertEquals($attributes, $this->entity->getAttributes());
    }
}
