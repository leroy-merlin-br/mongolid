---
sidebar_position: 11
---

# Sequences

`Mongolid\Util\SequenceService` provides auto-increment integers. Use it when the `_id` of a document must be a sequence instead of an `ObjectId`.

```php title="Getting the next value"
use Mongolid\Container\Container;
use Mongolid\Util\SequenceService;

$sequences = Container::make(SequenceService::class);

$sequences->getNextValue('products'); // 1
$sequences->getNextValue('products'); // 2
```

`getNextValue` receives the name of the sequence and returns an `int`. The first value of a new sequence is `1`.
Mongolid stores the counters in the `mongolid_sequences` collection, in the default database of the connection. Each counter is a document with the sequence name as `_id` and the last value in `seq`.

To use another collection, create the service yourself:

```php
$sequences = new SequenceService($connection, 'my_sequences');
```

## Using it in a Schema

A `Mongolid\Schema\Schema` (used by [`LegacyRecord`](legacy/record.md)) can declare a field with the `sequence` type:

```php
class ProductSchema extends \Mongolid\Schema\Schema
{
    public $collection = 'products';

    public $fields = [
        '_id' => 'sequence',
    ];
}
```

When the field value is empty (`null` or `0`), Mongolid uses the next value of the sequence named after the schema `$collection`, or its `$entityClass` when there is no collection. A value that is already set is kept.
