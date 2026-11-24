---
sidebar_position: 4
---

# Schema

A schema describes the shape of the documents of a collection. `LegacyRecord` uses it through its `$fields` property. `AbstractModel` does not use schemas.

## Defining fields on the model

```php title="Post.php"
class Post extends \Mongolid\LegacyRecord
{
    protected $collection = 'posts';

    protected $fields = [
        '_id' => 'objectId',
        'created_at' => 'createdAtTimestamp',
        'updated_at' => 'updatedAtTimestamp',
        'votes' => 'int',
        'number' => 'sequence',
    ];

    public $dynamic = false;
}
```

When `$dynamic` is `false`, attributes that are not listed in `$fields` are dropped when the document is saved. `LegacyRecord` defaults to `true`.

## Field types

| Type | Behavior |
|---|---|
| `objectId` | Generates an `ObjectId` when empty. Converts strings in ObjectId format. |
| `createdAtTimestamp` | Keeps an existing `UTCDateTime`. Otherwise uses the current time. |
| `updatedAtTimestamp` | Always uses the current time. |
| `sequence` | Uses the given integer, or gets the next value from the [sequence](../sequences.md) when empty. |
| `int`, `integer`, `bool`, `boolean`, `float`, `double`, `real`, `string` | Casts the value. `null` and empty arrays are kept as they are. |
| `schema.<Class>` | Maps the value (or each item of a list) with another schema. |

## Using a Schema class

Extend `Mongolid\Schema\Schema` and set `$fields`, `$collection`, `$dynamic` and `$entityClass`. `Schema` defaults to `$dynamic = false` and `$entityClass = 'stdClass'`. Use `Mongolid\Schema\DynamicSchema` as a base for `$dynamic = true`.

```php title="PostSchema.php"
class PostSchema extends \Mongolid\Schema\Schema
{
    public $collection = 'posts';

    public $entityClass = Post::class;

    public $fields = [
        '_id' => 'objectId',
        'author' => 'schema.' . AuthorSchema::class,
    ];
}
```

A `LegacyRecord` can point to a schema by setting `$fields` to the schema class name:

```php
    protected $fields = PostSchema::class;
```

If the schema has a method with the name of the field type, that method is used to parse the value. Add your own methods to create custom field types.
