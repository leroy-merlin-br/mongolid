<?php
namespace Mongolid\DataMapper;

use Mockery as m;
use Mongolid\Container\Ioc;
use Mongolid\Model\HasAttributesInterface;
use Mongolid\Schema\AbstractSchema;
use Mongolid\Schema\DynamicSchema;
use Mongolid\TestCase;
use stdClass;

class AbstractSchemaMapperTest extends TestCase
{
    public function testShouldMapToFieldsOfSchema()
    {
        // Arrange
        $this->instance(
            'My\Own\Schema',
            new class() extends AbstractSchema
            {
                /**
                 * {@inheritdoc}
                 */
                public $dynamic = true;

                /**
                 * {@inheritdoc}
                 */
                public $fields = [];
            }
        );

        $schema = new class() extends AbstractSchema
        {
            /**
             * {@inheritdoc}
             */
            public $dynamic = true;

            /**
             * {@inheritdoc}
             */
            public $fields = [
                'name' => 'string',
                'surname' => 'string',
                'age' => 'int',
                'stuff' => 'schema.My\Own\Schema',
            ];
        };

        $schemaMapper = new SchemaMapper($schema);
        $stuff = new stdClass();
        $stuff->address = '1, Blue Street';

        $otherStuff = new stdClass();
        $otherStuff->address = '2, Green Street';

        $data = [
            'name' => 'John',
            'surname' => null,
            'age' => '23',
            'stuff' => [$stuff, $otherStuff],
            'invalid' => null,
            'empty' => '',
        ];

        $expected = [
            'name' => 'John',
            'age' => 23,
            'stuff' => [['address' => '1, Blue Street'], ['address' => '2, Green Street']],
            'empty' => '',
        ];

        // Act
        $result = $schemaMapper->map($data);

        // Assert
        $this->assertSame($expected, $result);
    }

    public function testShouldClearDynamicFieldsIfSchemaIsNotDynamic()
    {
        // Arrange
        $schema = new class extends AbstractSchema
        {
            /**
             * {@inheritdoc}
             */
            public $fields = [
                'name' => 'string',
                'age' => 'int',
            ];
        };
        $schemaMapper = new SchemaMapper($schema);
        $data = [
            'name' => 'John',
            'age' => 23,
            'location' => 'Brazil',
        ];

        // Assert
        $this->callProtected($schemaMapper, 'clearDynamic', [&$data]);
        $this->assertEquals(
            [
                'name' => 'John',
                'age' => 23,
            ],
            $data
        );
    }

    public function testShouldNotClearDynamicFieldsIfSchemaIsDynamic()
    {
        // Arrange
        $schema = new class extends DynamicSchema
        {
            /**
             * {@inheritdoc}
             */
            public $fields = [
                'name' => 'string',
                'age' => 'int',
            ];
        };
        $schemaMapper = new SchemaMapper($schema);
        $data = [
            'name' => 'John',
            'age' => 23,
            'location' => 'Brazil',
        ];

        // Assert
        $this->callProtected($schemaMapper, 'clearDynamic', [&$data]);
        $this->assertEquals(
            [
                'name' => 'John',
                'age' => 23,
                'location' => 'Brazil',
            ],
            $data
        );
    }

    public function testShouldParseFieldIntoCastableType()
    {
        // Arrange
        $schema = new class extends AbstractSchema
        {
        };
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
        $schema = new class extends AbstractSchema
        {
        };
        $schemaMapper = m::mock(
            SchemaMapper::class.'[mapToSchema]',
            [$schema]
        );
        $schemaMapper->shouldAllowMockingProtectedMethods();

        // Act
        $schemaMapper->expects()
            ->mapToSchema(['foo' => 'bar'], 'FooBarSchema')
            ->andReturn(['foo' => 123]);

        // Assert
        $this->assertSame(
            ['foo' => 123],
            $schemaMapper->parseField(['foo' => 'bar'], 'schema.FooBarSchema')
        );
    }

    public function testShouldParseFieldUsingAMethodInSchemaIfTypeIsAnUnknownString()
    {
        // Arrange
        $schemaClass = new class() extends AbstractSchema
        {
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

    public function testShouldMapAnArrayValueToAnotherSchema()
    {
        // Arrange
        $schema = new class extends AbstractSchema
        {
        };
        $mySchema = $this->instance(
            'Xd\MySchema',
            new class extends AbstractSchema
            {
            }
        );
        $schemaMapper = new SchemaMapper($schema);
        $value = ['foo' => 'bar'];
        $test = $this;

        // When instantiating the SchemaMapper with the specified $param as dependency
        Ioc::bind(
            SchemaMapper::class,
            function ($container, $params) use ($value, $mySchema, $test) {
                // Check if mySchema has been injected correctly
                $test->assertSame($mySchema, $params['schema']);

                // Instantiate a SchemaMapper with mySchema
                $anotherSchemaMapper = m::mock(SchemaMapper::class, [$params['schema']]);

                // Set expectation to receive a map call
                $anotherSchemaMapper->expects()
                    ->map($value)
                    ->andReturn(['foo' => 'PARSED']);

                return $anotherSchemaMapper;
            }
        );

        // Act
        $result = $this->callProtected($schemaMapper, 'mapToSchema', [$value, 'Xd\MySchema']);

        // Assert
        $this->assertEquals([['foo' => 'PARSED']], $result);
    }

    public function testShouldParseToArrayGettingObjectAttributes()
    {
        // Arrange
        $schema = new class extends AbstractSchema
        {
        };
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
        $schema = new class extends AbstractSchema
        {
        };
        $schemaMapper = new SchemaMapper($schema);
        $object = ['age' => 25];

        // Assert
        $this->assertEquals(
            $object,
            $this->callProtected($schemaMapper, 'parseToArray', [$object])
        );
    }

    public function testShouldGetAttributesWhenObjectImplementsAttributesAccessInterface()
    {
        // Arrange
        $schema = new class extends AbstractSchema
        {
        };
        $schemaMapper = new SchemaMapper($schema);
        $object = new class implements HasAttributesInterface
        {
            public function getDocumentAttribute(string $key)
            {
            }

            public function getDocumentAttributes(): array
            {
                return ['foo' => 'bar'];
            }

            public function fill(array $input, bool $force = false)
            {
            }

            public function cleanDocumentAttribute(string $key)
            {
            }

            public function setDocumentAttribute(string $key, $value)
            {
            }

            public function syncOriginalDocumentAttributes()
            {
            }

            public function getOriginalDocumentAttributes(): array
            {
            }
        };

        // Assert
        $this->assertEquals(
            ['foo' => 'bar'],
            $this->callProtected($schemaMapper, 'parseToArray', [$object])
        );
    }
}
