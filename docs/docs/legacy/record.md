---
sidebar_position: 1
---

# Legacy Record

With `LegacyRecord` you can do most of the same actions you can do with `AbstractModel`.
Here you find the differences that `AbstractModel` can't do anymore, but `LegacyRecord` still can do for you.

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
- `fresh` returns `null` when no document with that `_id` exists anymore, for example after the document was deleted.
- `fresh` always queries the database and does not use the cache.

## Mutators

Set the public property `$mutable` to `true` to use mutators. The method names are `get` or `set`, the attribute name with the first letter in upper case, and `Attribute` (for example `getNameAttribute` for `name`).

```php
    class User extends \Mongolid\LegacyRecord
    {
        public $mutable = true;

        public function setNameAttribute($value)
        {
            return ucfirst($value);
        }
    }
```

The `set` mutator receives the value and the value it returns is stored. The `get` mutator takes no arguments.

:::info
The mutator names here differ from `AbstractModel`. On `AbstractModel` the methods end in `DocumentAttribute` (for example `getNameDocumentAttribute`), and `$mutable` is protected. See [Mutators](../mutators.md).
:::

## Casting

`$casts` works like on `AbstractModel`. See [Casting attributes](../casting.md).
