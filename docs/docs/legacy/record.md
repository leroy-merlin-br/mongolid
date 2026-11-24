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
