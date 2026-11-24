---
sidebar_position: 1
---

# Legacy Record

With `LegacyRecord` you can do pretty most the same actions that you can do with `AbstractModel`.
Here you find the differences that `AbstractModel` can't do anymore, but `LegacyRecord` still can do for you.

```php title="Mass assignment"
    $post = new Post;
    $post->fill(['title' => 'Bacon']);
```

```php title="Converting a model to an array"
    $user = User::first(1);
    return $user->toArray();
```

```php title="Reloading a model from the database"
    $fresh = $user->fresh();
```

## Mutators

Set `public $mutable = true;` and define `get<Attribute>Attribute` and `set<Attribute>Attribute` methods (the attribute name with the first letter in uppercase):

```php
    class User extends \Mongolid\LegacyRecord {
        public $mutable = true;

        public function getNameAttribute()
        {
            return strtoupper($this->attributes['name'] ?? '');
        }

        public function setNameAttribute($value)
        {
            return trim($value);
        }
    }
```

## Schema

`LegacyRecord` describes its document with the `$fields` property, which is `['_id' => 'objectId', 'created_at' => 'createdAtTimestamp', 'updated_at' => 'updatedAtTimestamp']` by default. Set `$fields` to the name of a `Mongolid\Schema\Schema` subclass to use it instead.
The `public $dynamic` property (default `true`) accepts fields that are not listed in `$fields`. `LegacyRecord` also supports `$collection`, `$writeConcern`, `$timestamps`, `$with`, `$casts`, `$fillable` and `$guarded`, and needs a `$collection` to be queried or saved (otherwise it throws `NoCollectionNameException`).

A `Schema` field can use the `sequence` type to get an auto-increment integer when the value is empty. See [Sequences](../sequences.md).
