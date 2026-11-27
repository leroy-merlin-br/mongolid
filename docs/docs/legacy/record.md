``---
sidebar_position: 1
---

# Legacy Record

`LegacyRecord` was the base class for all models in Mongolid, before `AbstractModel` was introduced. 
It is a wrapper around the MongoDB Driver, and it provides a simple and easy way to interact with MongoDB.

With `LegacyRecord` you can do most of the same actions you can do with `AbstractModel`.
But there are some differences that `LegacyRecord` can do that `AbstractModel` can't do anymore.

```php title="Mass assignment"
    $post = new Post;
    $post->fill(['title' => 'Bacon']);
```

`fill` is an instance method on `LegacyRecord`. 
It receives the input array and an optional `$force` flag, and returns the model. 
As on `AbstractModel`, an empty `$fillable` allows every attribute.



```php title="Converting a model to an array"
    $user = User::first(1);
    return $user->toArray();
```

`toArray` returns all attributes, including those set to `null`.



## Reloading a model from the database

`fresh` reads the model's document from the collection, using its `_id`, and returns a new instance with the stored attributes. 
It does not change the instance you call it on.

```php title="Reloading a model from the database"
    $user = User::first('4af9f23d8ead0e1d32000000');
    $user->name = 'Jane Doe';

    $fresh = $user->fresh();

    // $fresh->name is the value stored in the database, not 'Jane Doe'
    // $user->name is still 'Jane Doe' until you call save()
```

Keep these points in mind:

- Unsaved changes on `$user` are not included in `$fresh`.
- The return type of `fresh` is `self`. When no document with that `_id` exists anymore, for example after the document was deleted, PHP throws a `TypeError`.
- `fresh` always queries the database and does not use the cache.

## Mutators

Set the public property `$mutable` to `true` to use mutators. The method names are `get` or `set`, the attribute name with the first letter in upper case, and `Attribute` (for example `getNameAttribute` for `name`).

```php
    class User extends \Mongolid\LegacyRecord
    {
        public $mutable = true;

        // Mutator: runs on write. The returned value is what gets stored.
        public function setNameAttribute($value)
        {
            return ucfirst(trim($value));
        }

        // Accessor: runs on read. The returned value is what you receive.
        public function getNameAttribute()
        {
            return mb_convert_case($this->getAttribute('name'), MB_CASE_TITLE);
        }
    }
```

The `set` mutator receives the value and the value it returns is stored. The `get` accessor takes no arguments and its return value is what you receive when you read the attribute.

Inside the accessor, read the raw value with `$this->getAttribute('name')`. Reading `$this->name` would call the accessor again and recurse.

Here is how data is written and read with the `User` above:

```php
    $user = new User();

    // Write: the mutator runs, so the stored value is normalized.
    $user->name = '  joão da silva ';

    // Read with the property operator: the accessor runs.
    $user->name; // 'João Da Silva'

    // Raw value, as it is stored in the document and saved to the database.
    $user->getAttributes()['name']; // 'João da silva'

    $user->save(); // Persists { name: 'João da silva' }

    $user = User::first();
    $user->name; // 'João Da Silva' (the accessor runs again on read)
```

The mutator only changes what is stored, and the accessor only changes what you read. The database always holds the value returned by the `set` mutator, and the accessor is applied every time you read the property.

:::info
The mutator names here differ from `AbstractModel`. On `AbstractModel` the methods end in `DocumentAttribute` (for example `getNameDocumentAttribute`), and `$mutable` is protected. See [Mutators](../mutators.md).
:::

## Casting

`$casts` works like on `AbstractModel`. See [Casting attributes](../casting.md).
