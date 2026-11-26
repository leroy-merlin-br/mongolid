---
sidebar_position: 12
---

# Schema

A schema describes the documents of a collection: which fields they have, how each value is cast before it is saved, and which class is used for the entities you read back. The `DataMapper` uses a schema for every operation, and `LegacyRecord` builds one from its own properties.

## Defining a schema

Extend `Mongolid\Schema\Schema`:

```php title="PostSchema.php"
use Mongolid\Schema\Schema;

class PostSchema extends Schema
{
    public $collection = 'posts';

    public $entityClass = Post::class;

    public $fields = [
        '_id' => 'objectId',
        'title' => 'string',
        'views' => 'int',
        'author' => 'schema.AuthorSchema',
        'created_at' => 'createdAtTimestamp',
        'updated_at' => 'updatedAtTimestamp',
    ];
}
```

| Property | Default | Description |
|---|---|---|
| `$collection` | `null` | Collection the documents are saved to and read from. |
| `$fields` | `_id`, `created_at`, `updated_at` | Map of field name to field type. See [Field types](#field-types). |
| `$dynamic` | `false` | Whether fields that are not in `$fields` are kept. See [Dynamic schemas](#dynamic-schemas). |
| `$entityClass` | `stdClass` | Class of the objects returned when you read documents. |

## Using a schema with DataMapper

Give the schema to the `DataMapper` with `setSchema()`. `LegacyRecord` does this for you in `getDataMapper()`.

```php title="Using a schema"
    $dataMapper = Container::make(DataMapper::class);
    $dataMapper->setSchema(new PostSchema());

    $post = $dataMapper->first(['title' => 'Bacon']);
    $dataMapper->save($post);
```

:::info
`DataMapper::$schemaClass` defaults to `Schema::class`, which is abstract, so the default cannot be used as is. Call `setSchema()` with a concrete schema before reading. The `where()` and `first()` methods use the schema that is already set, so they do not create it from `$schemaClass`.
:::

## Field types

Each entry in `$fields` maps a field name to one of these types:

- **Cast types:** `int`, `integer`, `bool`, `boolean`, `float`, `double`, `real` and `string`. The value is converted with `settype()`.
- **Schema methods:** the name of a public method on the schema. The method receives the value and returns the value to save. The built-in methods are:
  - `objectId`: converts a valid ObjectId string to `ObjectId`. When the value is `null`, it creates a new `ObjectId`.
  - `sequence`: when the value is empty, returns the next number of an auto-increment sequence named after `$collection`, or `$entityClass` when there is no collection.
  - `createdAtTimestamp`: keeps an existing `UTCDateTime`. Otherwise it uses the current time.
  - `updatedAtTimestamp`: always uses the current time.
- **Embedded schemas:** `schema.<ClassName>`, where `<ClassName>` is a schema class. Each embedded document is mapped with that schema, and arrays of documents are mapped item by item.

```php title="Custom field method"
class PostSchema extends Schema
{
    public $fields = [
        'slug' => 'slugify',
    ];

    public function slugify($value)
    {
        return strtolower(trim((string) $value));
    }
}
```

:::warning
Schema methods are called even when the value is `null`. Other types skip `null` and empty arrays.
:::

## Dynamic schemas

When `$dynamic` is `false`, saving removes every field that is not in `$fields`. Set `$dynamic` to `true`, or extend `Mongolid\Schema\DynamicSchema`, to keep those fields.

```php title="Dynamic schema"
use Mongolid\Schema\DynamicSchema;

class LogSchema extends DynamicSchema
{
    public $collection = 'logs';

    public $fields = [
        'level' => 'string',
    ];
}
```

`LegacyRecord` builds a `DynamicSchema` from its own `$fields`, `$dynamic` and `$collection` properties. If `$fields` is the name of a schema class, it uses that class instead.

## Default fields

The base `Schema` defines `_id`, `created_at` and `updated_at`. Defining `$fields` in your schema replaces these defaults, so include them again if you still need them.
