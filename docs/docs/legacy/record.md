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

`fill` is an instance method on `LegacyRecord`. It receives the input array and an optional `$force` flag, and returns the model. As on `AbstractModel`, an empty `$fillable` allows every attribute.

```php title="Converting a model to an array"
    $user = User::first(1);
    return $user->toArray();
```

```php title="Reloading a model from the database"
    $fresh = $user->fresh();
```

`toArray` returns all attributes, including those set to `null`.

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

## Casting

`$casts` works like on `AbstractModel`. See [Casting attributes](../casting.md).
