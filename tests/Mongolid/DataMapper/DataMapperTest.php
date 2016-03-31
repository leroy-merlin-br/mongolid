<?php

namespace Mongolid\DataMapper;

use Mockery as m;
use Mongolid\Container\Ioc;
use Mongolid\Cursor\Cursor;
use Mongolid\Schema;
use stdClass;
use TestCase;

class DataMapperTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldBeAbleToConstructWithSchema()
    {
        // Arrange
        $connPool = m::mock('Mongolid\Connection\Pool');
        $mapper = new DataMapper($connPool);

        // Assertion
        $this->assertAttributeEquals($connPool, 'connPool', $mapper);
    }

    public function testShouldSave()
    {
        // Arrange
        $connPool = m::mock('Mongolid\Connection\Pool');
        $mapper   = m::mock('Mongolid\DataMapper\DataMapper[parseToDocument,getCollection]', [$connPool]);

        $collection      = m::mock('MongoDB\Collection');
        $object          = m::mock();
        $parsedObject    = ['_id' => 123];
        $operationResult = m::mock();

        $object->_id = null;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('parseToDocument')
            ->once()
            ->with($object)
            ->andReturn($parsedObject);

        $mapper->shouldReceive('getCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('updateOne')
            ->once()
            ->with(
                ['_id' => 123],
                ['$set' => $parsedObject],
                ['upsert' => true]
            )->andReturn($operationResult);

        $operationResult->shouldReceive('getModifiedCount', 'getUpsertedCount')
            ->once()
            ->andReturn(1);

        // Assert
        $this->assertTrue($mapper->save($object));
    }

    public function testShouldInsert()
    {
        // Arrange
        $connPool = m::mock('Mongolid\Connection\Pool');
        $mapper   = m::mock('Mongolid\DataMapper\DataMapper[parseToDocument,getCollection]', [$connPool]);

        $collection      = m::mock('MongoDB\Collection');
        $object          = m::mock();
        $parsedObject    = ['_id' => 123];
        $operationResult = m::mock();

        $object->_id = null;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('parseToDocument')
            ->once()
            ->with($object)
            ->andReturn($parsedObject);

        $mapper->shouldReceive('getCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('insertOne')
            ->once()
            ->with($parsedObject)
            ->andReturn($operationResult);

        $operationResult->shouldReceive('getInsertedCount')
            ->once()
            ->andReturn(1);

        // Assert
        $this->assertTrue($mapper->insert($object));
    }

    public function testShouldUpdate()
    {
        // Arrange
        $connPool = m::mock('Mongolid\Connection\Pool');
        $mapper   = m::mock('Mongolid\DataMapper\DataMapper[parseToDocument,getCollection]', [$connPool]);

        $collection      = m::mock('MongoDB\Collection');
        $object          = m::mock();
        $parsedObject    = ['_id' => 123];
        $operationResult = m::mock();

        $object->_id = null;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('parseToDocument')
            ->once()
            ->with($object)
            ->andReturn($parsedObject);

        $mapper->shouldReceive('getCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('updateOne')
            ->once()
            ->with(
                ['_id' => 123],
                ['$set' => $parsedObject]
            )->andReturn($operationResult);

        $operationResult->shouldReceive('getModifiedCount')
            ->once()
            ->andReturn(1);

        // Assert
        $this->assertTrue($mapper->update($object));
    }

    public function testShouldDelete()
    {
        // Arrange
        $connPool = m::mock('Mongolid\Connection\Pool');
        $mapper   = m::mock('Mongolid\DataMapper\DataMapper[parseToDocument,getCollection]', [$connPool]);

        $collection      = m::mock('MongoDB\Collection');
        $object          = m::mock();
        $parsedObject    = ['_id' => 123];
        $operationResult = m::mock();

        $object->_id = null;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('parseToDocument')
            ->once()
            ->with($object)
            ->andReturn($parsedObject);

        $mapper->shouldReceive('getCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('deleteOne')
            ->once()
            ->with(['_id' => 123])
            ->andReturn($operationResult);

        $operationResult->shouldReceive('getDeletedCount')
            ->once()
            ->andReturn(1);

        // Assert
        $this->assertTrue($mapper->delete($object));
    }

    public function testShouldGetWithWhereQuery()
    {
        // Arrange
        $connPool = m::mock('Mongolid\Connection\Pool');
        $mapper   = m::mock('Mongolid\DataMapper\DataMapper[prepareValueQuery,getCollection]', [$connPool]);
        $schema   = m::mock(Schema::class);

        $collection      = m::mock('MongoDB\Collection');
        $query           = 123;
        $preparedQuery   = ['_id' => 123];

        $schema->entityClass = 'stdClass';
        $mapper->schema = $schema;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('prepareValueQuery')
            ->once()
            ->with($query)
            ->andReturn($preparedQuery);

        $mapper->shouldReceive('getCollection')
            ->once()
            ->andReturn($collection);

        // Assert
        $result = $mapper->where($query);

        $this->assertInstanceOf(Cursor::class, $result);
        $this->assertAttributeEquals('stdClass', 'entityClass', $result);
        $this->assertAttributeEquals($collection, 'collection', $result);
        $this->assertAttributeEquals('find', 'command', $result);
        $this->assertAttributeEquals([$preparedQuery], 'params', $result);
    }

    public function testShouldGetAll()
    {
        // Arrange
        $connPool = m::mock('Mongolid\Connection\Pool');
        $mapper         = m::mock('Mongolid\DataMapper\DataMapper[where]', [$connPool]);
        $mongolidCursor = m::mock('Mongolid\Cursor\Cursor');

        // Act
        $mapper->shouldReceive('where')
            ->once()
            ->with([])
            ->andReturn($mongolidCursor);

        // Assert
        $this->assertEquals(
            $mongolidCursor,
            $mapper->all()
        );
    }

    public function testShouldGetFirstWithQuery()
    {
        // Arrange
        $connPool = m::mock('Mongolid\Connection\Pool');
        $mapper   = m::mock('Mongolid\DataMapper\DataMapper[prepareValueQuery,getCollection]', [$connPool]);
        $schema   = m::mock(Schema::class);

        $collection      = m::mock('MongoDB\Collection');
        $query           = 123;
        $preparedQuery   = ['_id' => 123];

        $schema->entityClass = 'stdClass';
        $mapper->schema = $schema;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('prepareValueQuery')
            ->once()
            ->with($query)
            ->andReturn($preparedQuery);

        $mapper->shouldReceive('getCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('findOne')
            ->once()
            ->with($preparedQuery)
            ->andReturn(['name' => 'John Doe']);

        // Assert
        $result = $mapper->first($query);

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertAttributeEquals('John Doe', 'name', $result);
    }

    public function testShouldGetNullIfFirstCantFindAnything()
    {
        // Arrange
        $connPool = m::mock('Mongolid\Connection\Pool');
        $mapper   = m::mock('Mongolid\DataMapper\DataMapper[prepareValueQuery,getCollection]', [$connPool]);
        $schema   = m::mock(Schema::class);

        $collection      = m::mock('MongoDB\Collection');
        $query           = 123;
        $preparedQuery   = ['_id' => 123];

        $schema->entityClass = 'stdClass';
        $mapper->schema = $schema;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('prepareValueQuery')
            ->once()
            ->with($query)
            ->andReturn($preparedQuery);

        $mapper->shouldReceive('getCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('findOne')
            ->once()
            ->with($preparedQuery)
            ->andReturn(null);

        // Assert
        $result = $mapper->first($query);

        $this->assertNull($result);
    }

    public function testShouldParseObjectToDocument()
    {
        // Arrange
        $connPool = m::mock('Mongolid\Connection\Pool');
        $mapper = m::mock('Mongolid\DataMapper\DataMapper[getSchemaMapper,parseToArray]', [$connPool]);
        $object         = m::mock();
        $parsedDocument = ['a_field' => 123];
        $schemaMapper   = m::mock('Mongolid\Schema[]');

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('getSchemaMapper')
            ->once()
            ->andReturn($schemaMapper);

        $mapper->shouldReceive('parseToArray')
            ->once()
            ->with($object)
            ->andReturn(['a_field' => '123']);

        $schemaMapper->shouldReceive('map')
            ->once()
            ->with(['a_field' => '123'])
            ->andReturn($parsedDocument);

        // Assert
        $this->assertEquals(
            $parsedDocument,
            $this->callProtected($mapper, 'parseToDocument', $object)
        );
    }

    public function testShouldGetSchemaMapper()
    {
        // Arrange
        $connPool = m::mock('Mongolid\Connection\Pool');
        $mapper = new DataMapper($connPool);
        $mapper->schemaClass = 'MySchema';
        $schema = m::mock('Mongolid\Schema');

        // Act
        Ioc::instance('MySchema', $schema);

        // Assert
        $result = $this->callProtected($mapper, 'getSchemaMapper');
        $this->assertInstanceOf('Mongolid\DataMapper\SchemaMapper', $result);
        $this->assertEquals($schema, $result->schema);
    }

    public function testShouldGetAttributesWhenGetattributesMethodIsAvailable()
    {
        // Arrange
        $connPool = m::mock('Mongolid\Connection\Pool');
        $mapper = new DataMapper($connPool);
        $object = m::mock(new __entity_stub);

        // Act
        $object->shouldReceive('getAttributes')
            ->once()
            ->andReturn(['foo' => 'bar']);

        // Assert
        $this->assertEquals(
            ['foo' => 'bar'],
            $this->callProtected($mapper, 'parseToArray', $object)
        );
    }

    public function testShouldParseToArrayGettingObjectAttributes()
    {
        // Arrange
        $connPool = m::mock('Mongolid\Connection\Pool');
        $mapper = new DataMapper($connPool);
        $object = m::mock();
        $object->foo  = 'bar';
        $object->name = 'wilson';

        // Assert
        $this->assertEquals(
            ['foo' => 'bar', 'name' => 'wilson'],
            $this->callProtected($mapper, 'parseToArray', $object)
        );
    }
}

class __entity_stub {
    public function getAttributes() {}
}
