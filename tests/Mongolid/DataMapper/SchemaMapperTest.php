<?php
namespace Mongolid\DataMapper;

use TestCase;
use Mockery as m;
use Mongolid\Container\Ioc;

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
        $schema = m::mock('Mongolid\Schema[]');
        $schema->fields = [
            'name'  => 'string',
            'age'   => 'int',
            'stuff' => 'schema.My\Own\Schema'
        ];
        $schemaMapper = m::mock(
            'Mongolid\DataMapper\SchemaMapper'.
            '[parseDynamicAttributes,parseField]',
            [$schema]
        );
        $schemaMapper->shouldAllowMockingProtectedMethods();
        $data = [
            'name'  => 'John',
            'age'   => 23,
            'stuff' => 'fooBar'
        ];

        // Act
        $schemaMapper->shouldReceive('parseDynamicAttributes')
            ->once()
            ->with($data);

        foreach($schema->fields as $key => $value) {
            $schemaMapper->shouldReceive('parseField')
                ->once()
                ->with($data[$key], $value)
                ->andReturn($data[$key].'.PARSED');
        }

        // Assert
        $this->assertEquals(
            [
                'name'  => 'John.PARSED',
                'age'   => '23.PARSED',
                'stuff' => 'fooBar.PARSED'
            ],
            $schemaMapper->map($data)
        );
    }
}
