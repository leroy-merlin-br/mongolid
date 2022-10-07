# Legacy Record

With `LegacyRecord` you can do pretty most the same actions that you can do with `AbstractModel`.
Here you find the differences that `AbstractModel` can't do anymore, but `LegacyRecord` still can do for you.

```php title="Mass assignment"
    $post = new Post;
    $post->fill(['title' => 'Bacon']);
```

```php title="Converting a model to json"
    return User::find(1)->toJson();
```
