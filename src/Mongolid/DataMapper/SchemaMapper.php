<?php
namespace Mongolid\DataMapper;

use Mongolid\Container\Ioc;
use Mongolid\Schema;
use Mongolid\Serializer\Type\Converter;

/**
 * The SchemaMapper will map an object or an array of data to an Schema object.
 * When instantiating a SchemaMapper you should provide a Schema. When calling
 * 'map' the Schema provided will be used to format the data to the correct
 * format.
 *
 * This class is meant to do the opposite of the EntityAssembler
 *
 * @package  Mongolid
 */
class SchemaMapper
{
    /**
     * The actual schema to maps the data
     *
     * @var Schema
     */
    public $schema;

    /**
     * Types that can be casted
     *
     * @see http://php.net/manual/en/language.types.type-juggling.php
     * @var string[]
     */
    protected $castableTypes = ['int','integer','bool','boolean','float','double','real','string'];

    /**
     * @param Schema $schema Schema that will be used to map each field.
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Maps the input $data to the schema specified in the $schema property
     *
     * @param  array|object $data Array or object with the fields that should
     *                            be mapped to $this->schema specifications.
     *
     * @return array
     */
    public function map($data)
    {
        $data = $this->parseToArray($data);
        $this->clearDynamic($data);

        // Parse each specified field
        foreach ($this->schema->fields as $key => $fieldType) {
            $data[$key] = $this->parseField($data[$key] ?? null, $fieldType);
        }

        $data = Ioc::make(Converter::class)->toMongoTypes($data);

        return $data;
    }

    /**
     * If the schema is not dynamic, remove all non specified fields
     *
     * @param array $data Reference of the fields. The passed array will be modified.
     *
     * @return  void
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
     * @param  mixed  $value     Value to be parsed.
     * @param  string $fieldType Description of how the field should be treated.
     *
     * @return mixed  $value Value parsed to match $type
     */
    public function parseField($value, string $fieldType)
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
     * @param  mixed  $value Value to be casted.
     * @param  string $type  Type to which the $value should be casted to.
     *
     * @return mixed
     */
    protected function cast($value, string $type)
    {
        settype($value, $type);
        return $value;
    }

    /**
     * Instantiate another SchemaMapper with the given $schemaClass and maps
     * the given $value
     *
     * @param mixed  $value       Value that will be mapped.
     * @param string $schemaClass Class that will be passed to the new SchemaMapper constructor.
     *
     * @return mixed
     */
    protected function mapToSchema($value, string $schemaClass)
    {
        $value  = (array)$value;
        $schema = Ioc::make($schemaClass);
        $mapper = Ioc::make(SchemaMapper::class, [$schema]);

        if (! isset($value[0])) {
            $value = [$value];
        }

        foreach ($value as $key => $subValue) {
            $value[$key] = $mapper->map($subValue);
        }

        return $value;
    }

    /**
     * Parses an object to an array before sending it to the SchemaMapper
     *
     * @param  mixed $object The object that will be transformed into an array.
     *
     * @return array
     */
    protected function parseToArray($object): array
    {
        if (! is_array($object)) {
            $attributes = method_exists($object, 'getAttributes')
                ? $object->getAttributes()
                : get_object_vars($object);

            return $attributes;
        }

        return $object;
    }
}
