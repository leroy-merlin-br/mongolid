<?php

namespace Mongolid\DataMapper;

use Mockery as m;
use Mongolid\Container\Ioc;
use Mongolid\Schema;
use Mongolid\Serializer\Type\Converter;
use TestCase;

class SchemaMapperTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldMapToFieldsOfSchema()
    {
        // Arrange
        $schema = m::mock(Schema::class);
        $converter = m::mock(Converter::class);
        $schema->fields = [
            'name'  => 'string',
            'age'   => 'int',
            'stuff' => 'schema.My\Own\Schema',
        ];
        $schemaMapper = m::mock(
            'Mongolid\DataMapper\SchemaMapper'.
            '[clearDynamic,parseField]',
            [$schema]
        );
        $schemaMapper->shouldAllowMockingProtectedMethods();
        $data = [
            'name'  => 'John',
            'age'   => 23,
            'stuff' => 'fooBar',
        ];

        // Act
        $schemaMapper->shouldReceive('clearDynamic')
            ->once()
            ->with($data);

        foreach ($schema->fields as $key => $value) {
            $schemaMapper->shouldReceive('parseField')
                ->once()
                ->with($data[$key], $value)
                ->andReturn($data[$key].'.PARSED');
        }

        Ioc::instance(Converter::class, $converter);

        $converter->shouldReceive('toMongoTypes')
            ->once()
            ->andReturnUsing(function ($data) {
                foreach ($data as $key => $value) {
                    $data[$key] = str_replace('23', 'batata', $value);
                }

                return $data;
            });

        // Assert
        $this->assertEquals(
            [
                'name'  => 'John.PARSED',
                'age'   => 'batata.PARSED',
                'stuff' => 'fooBar.PARSED',
            ],
            $schemaMapper->map($data)
        );
    }

    public function testShouldClearDynamicFieldsIfSchemaIsNotDynamic()
    {
        // Arrange
        $schema = m::mock(Schema::class);
        $schema->dynamic = false;
        $schema->fields = [
            'name'  => 'string',
            'age'   => 'int',
        ];
        $schemaMapper = new SchemaMapper($schema);
        $data = [
            'name'     => 'John',
            'age'      => 23,
            'location' => 'Brazil',
        ];

        // Assert
        $this->callProtected($schemaMapper, 'clearDynamic', [&$data]);
        $this->assertEquals(
            [
                'name' => 'John',
                'age'  => 23,
            ],
            $data
        );
    }

    public function testShouldNotClearDynamicFieldsIfSchemaIsDynamic()
    {
        // Arrange
        $schema = m::mock(Schema::class);
        $schema->dynamic = true;
        $schema->fields = [
            'name'  => 'string',
            'age'   => 'int',
        ];
        $schemaMapper = new SchemaMapper($schema);
        $data = [
            'name'     => 'John',
            'age'      => 23,
            'location' => 'Brazil',
        ];

        // Assert
        $this->callProtected($schemaMapper, 'clearDynamic', [&$data]);
        $this->assertEquals(
            [
                'name'     => 'John',
                'age'      => 23,
                'location' => 'Brazil',
            ],
            $data
        );
    }

    public function testShouldParseFieldIntoCastableType()
    {
        // Arrange
        $schema = m::mock(Schema::class);
        $schemaMapper = new SchemaMapper($schema);

        // Assert
        $this->assertSame(
            23,
            $schemaMapper->parseField('23', 'int')
        );

        $this->assertSame(
            '1234',
            $schemaMapper->parseField(1234, 'string')
        );
    }

    public function testShouldParseFieldIntoAnotherMappedSchemaIfTypeBeginsWithSchema()
    {
        // Arrange
        $schema = m::mock(Schema::class);
        $schemaMapper = m::mock(
            'Mongolid\DataMapper\SchemaMapper'.
            '[mapToSchema]',
            [$schema]
        );
        $schemaMapper->shouldAllowMockingProtectedMethods();

        // Act
        $schemaMapper->shouldReceive('mapToSchema')
            ->once()
            ->with(['foo' => 'bar'], 'FooBarSchema')
            ->andReturn(['foo' => 123]);

        // Assert
        $this->assertSame(
            ['foo' => 123],
            $schemaMapper->parseField(['foo' => 'bar'], 'schema.FooBarSchema')
        );
    }

    public function testShouldParseFieldUsingAMethodInSchemaIfTypeIsAnUnknowString()
    {
        // Arrange
        $schemaClass = new class() extends Schema {
            public function pumpkinPoint($value)
            {
                return $value * 2;
            }
        };

        $schema = new $schemaClass();
        $schemaMapper = new SchemaMapper($schema);

        // Assert
        $this->assertSame(
            6,
            $schemaMapper->parseField(3, 'pumpkinPoint')
        );
    }

    public function testShouldMapAnArrayValueToAnotherSchemaSchema()
    {
        // Arrange
        $schema = m::mock(Schema::class);
        $mySchema = m::mock(Schema::class);
        $schemaMapper = new SchemaMapper($schema);
        $value = ['foo' => 'bar'];
        $test = $this;

        // Act
        Ioc::instance('Xd\MySchema', $mySchema); // Register MySchema in Ioc

        // When instantiating the SchemaMapper with the specified $param as dependency
        Ioc::bind(SchemaMapper::class, function ($container, $params) use ($value, $mySchema, $test) {
            // Check if mySchema has been injected correctly
            $test->assertSame($mySchema, $params[0]);

            // Instantiate a SchemaMapper with mySchema
            $anotherSchemaMapper = m::mock(SchemaMapper::class, [$params[0]]);

            // Set expectation to receiva a map call
            $anotherSchemaMapper->shouldReceive('map')
                ->once()
                ->with($value)
                ->andReturn(['foo' => 'PARSED']);

            return $anotherSchemaMapper;
        });

        //Assert
        $this->assertEquals(
            [
                ['foo' => 'PARSED'],
            ],
            $this->callProtected($schemaMapper, 'mapToSchema', [$value, 'Xd\MySchema'])
        );
    }

    public function testShouldParseToArrayGettingObjectAttributes()
    {
        // Arrange
        $schema = m::mock(Schema::class);
        $schemaMapper = new SchemaMapper($schema);
        $object = (object) ['foo' => 'bar', 'name' => 'wilson'];

        // Assert
        $this->assertEquals(
            ['foo' => 'bar', 'name' => 'wilson'],
            $this->callProtected($schemaMapper, 'parseToArray', [$object])
        );
    }

    public function testShouldParseToArrayIfIsAnArray()
    {
        // Arrange
        $schema = m::mock(Schema::class);
        $schemaMapper = new SchemaMapper($schema);
        $object = ['age' => 25];

        // Assert
        $this->assertEquals(
            $object,
            $this->callProtected($schemaMapper, 'parseToArray', [$object])
        );
    }

    public function testShouldGetAttributesWhenGetAttributesMethodIsAvailable()
    {
        // Arrange
        $schema = m::mock(Schema::class);
        $schemaMapper = new SchemaMapper($schema);
        $object = new class() {
            public function getAttributes()
            {
                return ['foo' => 'bar'];
            }
        };

        // Assert
        $this->assertEquals(
            ['foo' => 'bar'],
            $this->callProtected($schemaMapper, 'parseToArray', [$object])
        );
    }
}
