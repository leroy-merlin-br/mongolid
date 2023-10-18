---
sidebar_position: 3
---

# Operations

## Insert, Update, Delete

To create a new document in the database from a model, simply create a new model instance and call the `save` method.

```php title="Saving a new model"
    $post = new Post();
    
    $post->title = 'Foo bar john doe';
    
    $post->save();
```

:::tip
Typically, your Mongolid models will have auto-generated `_id` keys.
However, if you wish to specify your own keys, set the `_id` attribute.
:::

To update a model, you may retrieve it, change an attribute, and use the `update` method:

```php title="Updating a retrieved model"
    $post = Post::first('4af9f23d8ead0e1d32000000');
    
    $post->subject = 'technology';
    
    $post->update();
```

To delete a model, simply call the `delete` method on the instance:

```php title="Deleting an existing model"
    $post = Post::first('4af9f23d8ead0e1d32000000');
    
    $post->delete();
```
## Reload a model from database

You can reload an instance from database by using `refresh()` method:

```php
    $post = Post::first('4af9f23d8ead0e1d32000000');

    $updatedPost = $post->refresh();
```
