---
sidebar_position: 13
---

# Sequences

A sequence gives you auto-increment integers. Use it when a document's `_id` (or any other field) must be a sequential number instead of an `ObjectId`. Mongolid stores the sequences in a MongoDB collection and gives each new value to the schema field that asks for one.

## Using a sequence in a schema

Set a field to the `sequence` method in the schema's `$fields`:

```php title="PostSchema.php"
use Mongolid\Schema\Schema;

class PostSchema extends Schema
{
    public $collection = 'posts';

    public $entityClass = Post::class;

    public $fields = [
        '_id' => 'sequence',
        'title' => 'string',
    ];
}
```

When you save a new document without a value for `_id`, Mongolid asks the `SequenceService` for the next number. A value you set yourself is kept as is.

```php title="Saving"
use Mongolid\Container\Container;
use Mongolid\DataMapper\DataMapper;

$dataMapper = Container::make(DataMapper::class);
$dataMapper->setSchema(new PostSchema());

$post = new Post();
$post->title = 'Bacon';

$dataMapper->save($post); // $post->_id is now 1
```

:::warning
Overriding `$fields` replaces the default fields, so `created_at` and `updated_at` are no longer set. Add them back if you need them.
:::

## How the next value is generated

The `sequence` method calls `Mongolid\Util\SequenceService::getNextValue()` with a sequence name:

- The name is the schema's `$collection`. If the schema has no collection, the name is its `$entityClass`.
- Each sequence is a document in the `mongolid_sequences` collection, with `_id` set to the sequence name and a `seq` field that holds the last value given.
- The first value of a sequence is `1`.

Each call increments the counter, so a number is not given to two documents. If the field already has a non-zero value, that value is kept and the counter is not used.

## Changing the sequence collection

`SequenceService` stores its counters in `mongolid_sequences` by default. To use a different collection, create the service with a new name and register it in the container before you save:

```php title="Custom sequence collection"
use Mongolid\Connection\Connection;
use Mongolid\Container\Container;
use Mongolid\Util\SequenceService;

$connection = Container::make(Connection::class);

Container::instance(SequenceService::class, new SequenceService($connection, 'counters'));
```

## Notes

- Sequence names are shared by every schema that uses the same `$collection` (or `$entityClass`). Two schemas with the same name share one counter.
- Values are not reused. If a save fails after the value was taken, that number is skipped.
