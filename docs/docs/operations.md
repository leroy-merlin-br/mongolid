``---
sidebar_position: 3
---

# Operations

## Insert, Update, Delete

To create a new document in the database from a model, simply create a new model instance and call the `save` method.
`save` is an upsert: it inserts the document or replaces the existing one with the same `_id`.
Use `insert` to force an insertion, which fails if the `_id` already exists.

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

`update` on a model without an `_id` inserts the document instead.

### What a write does to the model

Before `save`, `insert` or `update` write the document, Mongolid:

- Removes attributes set to `null`.
- Generates an `ObjectId` for `_id` when it is empty, and converts an ObjectId string to `ObjectId`.
- Sets `updated_at` to the current time, and sets `created_at` when it is not a `UTCDateTime` yet. Set `protected $timestamps = false;` on the model to turn this off. It defaults to `true`.
- Keeps attributes that are not in `$fillable` or `$guarded`, because `protected $dynamic` defaults to `true`. When it is `false`, only `_id`, the timestamps (when enabled) and the names listed in `$fillable` and `$guarded` are saved.

Writes use a write concern of `1`. Change it with `setWriteConcern(int $writeConcern)` or by overriding `protected $writeConcern`. `getWriteConcern()` returns the current value.

### About operations returns

- `insert` returns `true` when a document is inserted.
- `save` and `update` return `true` only when the write is acknowledged and the database inserted or modified a document, so they return `false` when nothing changed.
- `delete` returns `true` when a document is deleted. On a model that uses [soft delete](./soft-delete.md), it updates the document instead.

## Reload a model from database

You can reload an instance from database by using `fresh()` method. It returns a new instance and does not change the one you call it on:

```php
    $post = Post::first('4af9f23d8ead0e1d32000000');

    $updatedPost = $post->fresh();
```


Keep these points in mind:

- Unsaved changes on `$user` are not included in `$fresh`.
- The return type of `fresh` is `self`. When no document with that `_id` exists anymore, for example after the document was deleted, PHP throws a `TypeError` because `first` returns `null`.
- `fresh` always queries the database and does not use the cache.

`refresh()` is an alias of `fresh()`: it returns a new instance and has the same behavior and edge cases.

## Purge and create indexes

`purge()` is an alias of `delete()`. It returns nothing, so use `delete()` when you need the boolean result. On a model that uses [soft delete](./soft-delete.md), it soft deletes the document.

`createIndex($keys, $options = [])` creates an index on the model's collection and returns nothing. `$keys` and `$options` are passed as they are to the MongoDB driver's `createIndex`. See [MongoDB - Indexes](https://docs.mongodb.org/manual/indexes/).

```php
    $post = new Post();

    $post->createIndex(['title' => 1], ['unique' => true]);
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

The `where`, `all`, `first` and `firstOrFail` methods read the first argument as a query. It can be an array, or a single value that is used as `_id`. When the `_id` is a string in ObjectId format, or an `$in` / `$nin` list of them, it is converted to `ObjectId`. `first(null)` returns `null` without querying.

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

### Projections

The second argument of `where`, `first` and `firstOrFail` selects the fields to return. You can use the MongoDB format, with `1` or `true` to include a field and `0` or `false` to exclude it, or a list of field names.

```php title="Projections"
    $posts = Post::where([], ['title' => 1]);
    $posts = Post::where([], ['title' => true]);

    // Include title and exclude _id
    $posts = Post::where([], ['title', '-_id']);
```

Prefix a name with `-` in a list to exclude it. Any other value throws `InvalidArgumentException`.

## Bulk writes

Use `Mongolid\Query\BulkWrite` to group several `updateOne` operations in a single request. The collection comes from the given model.

```php title="Bulk update"
    $bulk = new \Mongolid\Query\BulkWrite(new Post());

    $bulk->updateOne(['_id' => $id], ['title' => 'New title']); // upserts by default
    $bulk->updateOne(['_id' => $id], ['draft' => ''], [], '$unset');

    $result = $bulk->execute(); // MongoDB\BulkWriteResult
```

`updateOne` receives the filter (an array, or an `_id` value), the data, the options and the update operator. The options default to `['upsert' => true]` and the operator to `$set`. Pass `[]` as the options to disable the upsert.

`execute` receives an optional write concern (default `1`). It throws `Mongolid\Model\Exception\NoCollectionNameException` when the model has no collection. The operations list is cleared after `execute`. Use `isEmpty()` and `count()` to inspect it.

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
