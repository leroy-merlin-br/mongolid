## Legacy Record

With `LegacyRecord` you can do pretty most the same actions that you can do with `AbstractModel`.
Here you find the differences that `AbstractModel` can't do anymore, but `LegacyRecord` still can do for you.

## Mass assignment

You can mass assign attributes using the `fill` method:

```php
$post = new Post;
$post->fill(['title' => 'Bacon']);
```

#### Converting a model to json

```php
return User::find(1)->toJson();
```
