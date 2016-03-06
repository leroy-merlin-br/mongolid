<?php
namespace Mongolid\DataMapper;

use TestCase;
use Mockery as m;
use Mongolid\Container\Ioc;

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
        $schema = m::mock('Mongolid\Schema[]');
        $mapper = new DataMapper($schema);

        // Assertion
        $this->assertEquals($schema, $mapper->schema);
    }

    public function testShouldSave()
    {
        // Arrange
        $mapper = m::mock('Mongolid\DataMapper\DataMapper[parseToDocument,performQuery]');
        $schema = m::mock('Mongolid\Schema[]');
        $object = m::mock();
        $parsedObject = m::mock();
        $mapper->schema = $schema;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('parseToDocument')
            ->once()
            ->with($object)
            ->andReturn($parsedObject);

        $mapper->shouldReceive('performQuery')
            ->once()
            ->with('upsert', 'mongolid', $parsedObject)
            ->andReturn(true);

        // Assert
        $this->assertTrue($mapper->save($object));
    }

    public function testShouldInsert()
    {
        // Arrange
        $mapper = m::mock('Mongolid\DataMapper\DataMapper[parseToDocument,performQuery]');
        $schema = m::mock('Mongolid\Schema[]');
        $object = m::mock();
        $parsedObject = m::mock();
        $mapper->schema = $schema;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('parseToDocument')
            ->once()
            ->with($object)
            ->andReturn($parsedObject);

        $mapper->shouldReceive('performQuery')
            ->once()
            ->with('insert', 'mongolid', $parsedObject)
            ->andReturn(true);

        // Assert
        $this->assertTrue($mapper->insert($object));
    }

    public function testShouldUpdate()
    {
        // Arrange
        $mapper = m::mock('Mongolid\DataMapper\DataMapper[parseToDocument,performQuery]');
        $schema = m::mock('Mongolid\Schema[]');
        $object = m::mock();
        $parsedObject = m::mock();
        $mapper->schema = $schema;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('parseToDocument')
            ->once()
            ->with($object)
            ->andReturn($parsedObject);

        $mapper->shouldReceive('performQuery')
            ->once()
            ->with('update', 'mongolid', $parsedObject)
            ->andReturn(true);

        // Assert
        $this->assertTrue($mapper->update($object));
    }

    public function testShouldGetWithWhereQuery()
    {
        // Arrange
        $mapper         = m::mock('Mongolid\DataMapper\DataMapper[getSchemaMapper,performQuery]');
        $rawCursor      = m::mock('MongoCursor');
        $mongolidCursor = m::mock('Mongolid\Cursor\Cursor');
        $schema         = m::mock('Mongolid\Schema[]');
        $schemaMapper   = m::mock();
        $query          = ['foo' => 'bar'];
        $test           = $this;

        $mapper->schema      = $schema;
        $schemaMapper->entityClass = 'stdClass';

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('getSchemaMapper')
            ->once()
            ->andReturn($schemaMapper);

        $mapper->shouldReceive('performQuery')
            ->once()
            ->with('where', 'mongolid', $query)
            ->andReturn($rawCursor);

        // Binds a closure that will assert if the constructor params of Cursor are correct
        Ioc::bind('Mongolid\Cursor\Cursor', function ($container, $params) use ($test, $rawCursor, $mongolidCursor) {
            $test->assertEquals($rawCursor, $params[0]);
            $test->assertEquals('stdClass', $params[1]);
            return $mongolidCursor;
        });

        // Assert
        $this->assertEquals(
            $mongolidCursor,
            $mapper->where($query)
        );
    }

    public function testShouldGetAll()
    {
        // Arrange
        $mapper         = m::mock('Mongolid\DataMapper\DataMapper[where]');
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
        $mapper         = m::mock('Mongolid\DataMapper\DataMapper[where]');
        $mongolidCursor = m::mock('Mongolid\Cursor\Cursor');
        $query          = ['foo' => 'bar'];

        // Act
        $mapper->shouldReceive('where')
            ->once()
            ->with($query)
            ->andReturn($mongolidCursor);

        $mongolidCursor->shouldReceive('limit')
            ->once()
            ->with(1)
            ->andReturn($mongolidCursor);

        $mongolidCursor->shouldReceive('first')
            ->once()
            ->andReturn($mongolidCursor);

        // Assert
        $this->assertEquals(
            $mongolidCursor,
            $mapper->first($query)
        );
    }

    public function testShouldParseObjectToDocument()
    {
        // Arrange
        $mapper = m::mock('Mongolid\DataMapper\DataMapper[getSchemaMapper,parseToArray]');
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
        $mapper = new DataMapper;
        $mapper->schemaClass = 'MySchema';
        $schema = m::mock('Mongolid\Schema');

        // Act
        Ioc::instance('MySchema', $schema);

        // Assert
        $result = $this->callProtected($mapper, 'getSchemaMapper');
        $this->assertInstanceOf('Mongolid\DataMapper\SchemaMapper', $result);
        $this->assertEquals($schema, $result->schema);
    }

    public function testShouldParseToArrayWhenToArrayMethodIsAvailable()
    {
        // Arrange
        $mapper = new DataMapper;
        $object = m::mock(new __entity_stub);

        // Act
        $object->shouldReceive('toArray')
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
        $mapper = new DataMapper;
        $object = m::mock();
        $object->foo  = 'bar';
        $object->name = 'wilson';

        // Assert
        $this->assertEquals(
            ['foo' => 'bar', 'name' => 'wilson'],
            $this->callProtected($mapper, 'parseToArray', $object)
        );
    }

    public function testShouldPerformQuery()
    {
        // Arrange
        $mapper     = new DataMapper;
        $queryObj   = m::mock();
        $collection = 'mongolid';
        $param      = ['foo' => 'bar'];
        $result     = 'result';

        // Act
        Ioc::instance('Mongolid\DataMapper\Query', $queryObj);

        $queryObj->shouldReceive('thaCommand')
            ->with('mongolid', $param)
            ->once()
            ->andReturn($result);

        // Assert
        $this->assertEquals(
            $result,
            $this->callProtected($mapper, 'performQuery', ['thaCommand', $collection, $param])
        );
    }
}

class __entity_stub {
    public function toArray() {}
}
