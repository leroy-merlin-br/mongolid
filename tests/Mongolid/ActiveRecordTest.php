<?php
namespace Mongolid;

use TestCase;
use Mockery as m;
use Mongolid\Container\Ioc;

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
        $entity = m::mock('Mongolid\ActiveRecord[]');

        // Assert
        $this->assertEquals('mongolid', $entity->collection);
        $this->assertAttributeEquals(['_id' => 'mongoId'], 'fields', $entity);
        $this->assertTrue($entity->dynamic);
    }

    public function testShouldSave()
    {
        // Arrage
        $entity = m::mock('Mongolid\ActiveRecord[getDataMapper]');
        $dataMapper = m::mock();

        // Act
        $entity->shouldAllowMockingProtectedMethods();

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

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
        $entity = m::mock('Mongolid\ActiveRecord[getDataMapper]');
        $dataMapper = m::mock();

        // Act
        $entity->shouldAllowMockingProtectedMethods();

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

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
        $entity = m::mock('Mongolid\ActiveRecord[getDataMapper]');
        $dataMapper = m::mock();

        // Act
        $entity->shouldAllowMockingProtectedMethods();

        $entity->shouldReceive('getDataMapper')
            ->andReturn($dataMapper);

        $dataMapper->shouldReceive('update')
            ->once()
            ->with($entity)
            ->andReturn(true);

        // Assert
        $this->assertTrue($entity->update());
    }

    public function testShouldGetWithWhereQuery()
    {
        // Arrage
        $entity = m::mock('Mongolid\ActiveRecord[getDataMapper]');
        $query      = ['foo' => 'bar'];
        $dataMapper = m::mock();
        $cursor     = m::mock();

        // Act
        $entity->shouldAllowMockingProtectedMethods();

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
        $entity = m::mock('Mongolid\ActiveRecord[getDataMapper]');
        $dataMapper = m::mock();
        $cursor     = m::mock();

        // Act
        $entity->shouldAllowMockingProtectedMethods();

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
        $entity = m::mock('Mongolid\ActiveRecord[getDataMapper]');
        $query      = ['foo' => 'bar'];
        $dataMapper = m::mock();
        $cursor     = m::mock();

        // Act
        $entity->shouldAllowMockingProtectedMethods();

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
        $entity = m::mock('Mongolid\ActiveRecord[]');
        $this->setProtected($entity, 'fields', 'MySchemaClass');
        $schema = m::mock();

        // Act
        Ioc::instance('MySchemaClass', $schema);

        // Assert
        $this->assertEquals($schema, $this->callProtected($entity, 'getSchema'));
    }

    public function testShouldGetSchemaIfFieldsDescribesSchemaFields()
    {
        // Arrage
        $entity = m::mock('Mongolid\ActiveRecord[]');
        $fields = ['name' => 'string', 'age' => 'int'];
        $this->setProtected($entity, 'fields', $fields);

        // Assert
        $result = $this->callProtected($entity, 'getSchema');
        $this->assertInstanceOf('Mongolid\Schema', $result);
        $this->assertEquals($fields, $result->fields);
        $this->assertEquals($entity->dynamic, $result->dynamic);
        $this->assertEquals(get_class($entity), $result->entityClass);
    }

    public function testShouldGetDataMapper()
    {
        // Arrage
        $entity = m::mock('Mongolid\ActiveRecord[getSchema]');
        $entity->collection = 'foobar';
        $schema = m::mock();

        // Act
        $entity->shouldAllowMockingProtectedMethods();

        $entity->shouldReceive('getSchema')
            ->once()
            ->andReturn($schema);

        // Assert
        $result = $this->callProtected($entity, 'getDataMapper');
        $this->assertInstanceOf('Mongolid\DataMapper\DataMapper', $result);
        $this->assertEquals($entity->collection, $result->collection);
        $this->assertEquals($schema, $result->schema);
    }
}
