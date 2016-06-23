<?php
namespace Mongolid;

use Mockery as m;
use Mongolid\Container\Ioc;
use Mongolid\Model\Attributes;
use Mongolid\Model\Relations;
use Mongolid\Schema;
use TestCase;

class ActiveRecordTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldHaveCorrectPropertiesByDefault()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[]');

        // Assert
        $this->assertAttributeEquals(
            [
                '_id' => 'objectId',
                'created_at' => 'createdAtTimestamp',
                'updated_at' => 'updatedAtTimestamp'
            ],
            'fields',
            $entity
        );
        $this->assertTrue($entity->dynamic);
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
        $entity = m::mock(ActiveRecord::class)->makePartial();
        $this->assertFalse($entity->save());
    }

    public function testUpdateShouldReturnFalseIfCollectionIsNull()
    {
        $entity = m::mock(ActiveRecord::class)->makePartial();
        $this->assertFalse($entity->update());
    }

    public function testInsertShouldReturnFalseIfCollectionIsNull()
    {
        $entity = m::mock(ActiveRecord::class)->makePartial();
        $this->assertFalse($entity->insert());
    }

    public function testDeleteShouldReturnFalseIfCollectionIsNull()
    {
        $entity = m::mock(ActiveRecord::class)->makePartial();
        $this->assertFalse($entity->delete());
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
            ->with($query)
            ->andReturn($cursor);

        // Assert
        $this->assertEquals($cursor, $entity->where($query));
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
            ->with($query)
            ->andReturn($cursor);

        // Assert
        $this->assertEquals($cursor, $entity->first($query));
    }

    public function testShouldGetSchemaIfFieldsIsTheClassName()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[]');
        $this->setProtected($entity, 'fields', 'MySchemaClass');
        $schema = m::mock(Schema::class);

        // Act
        Ioc::instance('MySchemaClass', $schema);

        // Assert
        $this->assertEquals($schema, $this->callProtected($entity, 'getSchema'));
    }

    public function testShouldGetSchemaIfFieldsDescribesSchemaFields()
    {
        // Arrage
        $entity = m::mock(ActiveRecord::class.'[]');
        $fields = ['name' => 'string', 'age' => 'int'];
        $this->setProtected($entity, 'fields', $fields);

        // Assert
        $result = $this->callProtected($entity, 'getSchema');
        $this->assertInstanceOf(Schema::class, $result);
        $this->assertEquals($fields, $result->fields);
        $this->assertEquals($entity->dynamic, $result->dynamic);
        $this->assertEquals($entity->getCollectionName(), $result->collection);
        $this->assertEquals(get_class($entity), $result->entityClass);
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
}
