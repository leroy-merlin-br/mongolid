---
sidebar_position: 3
---

# Operations

## Insert, Update, Delete

To create a new document in the database from a model, simply create a new model instance and call the `save` method.
`save` is an upsert: it inserts the document or replaces the existing one with the same `_id`.
Use `insert` to force an insertion, which fails if the `_id` already exists.
`insert` returns `true` when a document is inserted. `save` and `update` return `true` only when the write is acknowledged and the database inserted or modified a document, so they return `false` when nothing changed. `delete` returns `true` when a document is deleted.

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

You can reload an instance from database by using the `fresh()` method (or its alias `refresh()`):

```php
    $post = Post::first('4af9f23d8ead0e1d32000000');

    $updatedPost = $post->fresh();
```

## Querying

`where`, `all`, `first` and `firstOrFail` are available statically on every model.

```php title="Query methods"
    // Cursor of matching models. The second argument is a projection
    $posts = Post::where(['category' => 'coding'], ['title' => 1]);

    // First matching model, or null
    $post = Post::first(['title' => 'Bacon']);

    // Throws Mongolid\Model\Exception\ModelNotFoundException when nothing matches
    $post = Post::firstOrFail('4af9f23d8ead0e1d32000000');

    // Returns the existing model, or a new one with the given _id
    $post = Post::firstOrNew('4af9f23d8ead0e1d32000000');
```

The projection accepts the MongoDB format (`['title' => 1]` or `['title' => true]`), or a list of field names. Prefix a name with `-` to exclude it, as in `['title', '-_id']`. Any other value throws `InvalidArgumentException`.

Pass `true` as the last argument of `where` and `first` to store the query results in the `Mongolid\Util\CacheComponentInterface` bound in the container. The next identical query reads from the cache.

```php title="Using the cache"
    $post = Post::first($id, [], true);
```

:::info
On `AbstractModel`, the third argument only caches query results. It does not eager load the models listed in `$with`. Eager loading is available on `LegacyRecord` and `DataMapper`. See [Eager loading](./eager-loading.md).
:::

:::info
`AbstractModel::firstOrFail` accepts `$useCache` but does not pass it on, so it always reads from the database.
:::

## Bulk writes

Use `Mongolid\Query\BulkWrite` to group several `updateOne` operations in a single request. The collection comes from the given model.

```php title="Bulk update"
    $bulk = new \Mongolid\Query\BulkWrite(new Post());

    $bulk->updateOne(['_id' => $id], ['title' => 'New title']); // upserts by default
    $bulk->updateOne(['_id' => $id], ['draft' => ''], [], '$unset');

    $result = $bulk->execute(); // MongoDB\BulkWriteResult
```

The operations list is cleared after `execute`. Use `isEmpty()` and `count()` to inspect it.

## Updating many documents

To update many documents at once, use the `updateMany` method on the model's collection. 
This method allows you to specify a filter and an update operation, and it will apply the update to all documents that match the filter.

`updateMany` is not a Mongolid method. Use the `MongoDB\Collection` returned by `getCollection()` and call the driver's [`updateMany`](https://www.mongodb.com/docs/php-library/current/crud/update/) directly. 
This skips model events and attribute casting, because it writes the raw document.

```php title="Update many"
    $result = (new Post())->getCollection()->updateMany(
        ['category' => 'coding'],
        ['$set' => ['published' => true]]
    );

    echo $result->getModifiedCount();
```
