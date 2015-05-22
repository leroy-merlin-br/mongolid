<?php
namespace Mongolid\DataMapper;

use Mongolid\Schema;
use Mongolid\Container\Ioc;

/**
 * The SchemaMapper will map an array of data to an Schema object. When
 * instantiating a SchemaMapper you should provide a Schema. When calling 'map'
 * the Schema provided will be used to format the data to the correct format.
 *
 * @package  Mongolid
 */
class SchemaMapper
{
    /**
     * The actual schema to maps the data
     * @var Schema
     */
    public $schema;

    /**
     * Types that can be casted
     * @see http://php.net/manual/en/language.types.type-juggling.php
     * @var string[]
     */
    protected $castableTypes = ['int','integer','bool','boolean','float','double','real','string'];

    /**
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Maps the input $data to the schema specified in the $schema property
     *
     * @param  array  $data
     *
     * @return array
     */
    public function map(array $data)
    {
        $this->clearDynamic($data);

        // Parse each specified field
        foreach ($this->schema->fields as $key => $fieldType) {
            if (isset($data[$key])) {
                $data[$key] = $this->parseField($data[$key], $fieldType);
            }
        }

        return $data;
    }

    /**
     * If the schema is not dynamic, remove all non specified fields
     *
     * @param  array  &$data
     */
    protected function clearDynamic(array &$data)
    {
        if (! $this->schema->dynamic) {
            $data = array_intersect_key($data, $this->schema->fields);
        }
    }

    /**
     * Parse a value based on a field yype of the schema
     *
     * @param  mixed  $value Value to be parsed
     * @param  string $fieldType  Description of how the field should be treated
     *
     * @return mixed  $value Value parsed to match $type
     */
    public function parseField($value, $fieldType)
    {
        // If fieldType is castable (Ex: 'int')
        if (in_array($fieldType, $this->castableTypes)) {
            return $this->cast($value, $fieldType);
        }

        // If the field type points to another schema.
        if (substr($fieldType, 0, 7) == 'schema.') {
            return $this->mapToSchema($value, substr($fieldType, 7));
        }

        // Uses $fieldType method of the schema to parse the value
        return $this->schema->$fieldType($value);
    }

    /**
     * Uses PHP's settype to cast a value to a type
     *
     * @see http://php.net/manual/pt_BR/function.settype.php
     *
     * @param  mixed  $value
     * @param  string $type
     *
     * @return mixed
     */
    protected function cast($value, $type)
    {
        settype($value, $type);
        return $value;
    }

    /**
     * Instantiate another SchemaMapper with the given $schemaClass and maps
     * the given $value
     *
     * @param  mixed $value
     * @param  string $schemaClass Name of the class that will be instantiated and passed to the new SchemaMapper constructor
     *
     * @return mixed
     */
    protected function mapToSchema($value, $schemaClass)
    {
        if (is_array($value)) {
            $schema = Ioc::make($schemaClass);
            $mapper = Ioc::make('Mongolid\DataMapper\SchemaMapper', [$schema]);
            return $mapper->map($value);
        }

        return null;
    }
}
