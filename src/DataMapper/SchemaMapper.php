<?php

namespace Mongolid\DataMapper;

use Mongolid\Container\Container;
use Mongolid\Container\Ioc;
use Mongolid\Schema\Schema;

/**
 * The SchemaMapper will map an object or an array of data to a Schema object.
 * When instantiating a SchemaMapper you should provide a Schema. When calling
 * 'map' the Schema provided will be used to format the data to the correct
 * format.
 *
 * This class is meant to do the opposite of the EntityAssembler
 */
class SchemaMapper
{
    /**
     * Types that can be casted.
     *
     * @see http://php.net/manual/en/language.types.type-juggling.php
     *
     * @var string[]
     */
    protected array $castableTypes = ['int', 'integer', 'bool', 'boolean', 'float', 'double', 'real', 'string'];

    /**
     * @param Schema $schema schema that will be used to map each field
     */
    public function __construct(
        /**
         * The actual schema to maps the data.
         */
        public Schema $schema
    ) {
    }

    /**
     * Maps the input $data to the schema specified in the $schema property.
     *
     * @param array|object $data array or object with the fields that should
     *                           be mapped to $this->schema specifications
     *
     * @return array
     */
    public function map(array|object $data): array
    {
        $data = $this->parseToArray($data);
        $this->clearDynamic($data);

        // Parse each specified field
        foreach ($this->schema->fields as $key => $fieldType) {
            $data[$key] = $this->parseField($data[$key] ?? null, $fieldType);
        }

        return $data;
    }

    /**
     * If the schema is not dynamic, remove all non specified fields.
     *
     * @param array $data Reference of the fields. The passed array will be modified.
     */
    protected function clearDynamic(array &$data): void
    {
        if (!$this->schema->dynamic) {
            $data = array_intersect_key($data, $this->schema->fields);
        }
    }

    /**
     * Parse a value based on a field type of the schema.
     *
     * @param mixed  $value     value to be parsed
     * @param string $fieldType description of how the field should be treated
     *
     * @return mixed $value Value parsed to match $type
     */
    public function parseField(mixed $value, string $fieldType): mixed
    {
        // Uses $fieldType method of the schema to parse the value
        if (method_exists($this->schema, $fieldType)) {
            return $this->schema->$fieldType($value);
        }
        // Returns null or an empty array
        if (null === $value || is_array($value) && empty($value)) {
            return $value;
        }

        // If fieldType is castable (Ex: 'int')
        if (in_array($fieldType, $this->castableTypes)) {
            return $this->cast($value, $fieldType);
        }

        // If the field type points to another schema.
        if (str_starts_with($fieldType, 'schema.')) {
            return $this->mapToSchema($value, substr($fieldType, 7));
        }

        return $value;
    }

    /**
     * Uses PHP's set type to cast a value to a type.
     *
     * @see http://php.net/manual/pt_BR/function.settype.php
     *
     * @param mixed  $value value to be casted
     * @param string $type  type to which the $value should be casted to
     *
     * @return mixed
     */
    protected function cast(mixed $value, string $type): mixed
    {
        settype($value, $type);

        return $value;
    }

    /**
     * Instantiate another SchemaMapper with the given $schemaClass and maps
     * the given $value.
     *
     * @param mixed  $value       value that will be mapped
     * @param string $schemaClass class that will be passed to the new SchemaMapper constructor
     */
    protected function mapToSchema(mixed $value, string $schemaClass): array
    {
        $value = (array) $value;
        $schema = Container::make($schemaClass);
        $mapper = Container::make(self::class, compact('schema'));

        if (!isset($value[0])) {
            $value = [$value];
        }

        foreach ($value as $key => $subValue) {
            $value[$key] = $mapper->map($subValue);
        }

        return $value;
    }

    /**
     * Parses an object to an array before sending it to the SchemaMapper.
     *
     * @param mixed $object the object that will be transformed into an array
     */
    protected function parseToArray(mixed $object): array
    {
        if (!is_array($object)) {
            return method_exists($object, 'getAttributes')
                ? $object->getAttributes()
                : get_object_vars($object);
        }

        return $object;
    }
}
