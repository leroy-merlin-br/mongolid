---
sidebar_position: 11
---

# Sequences

`Mongolid\Util\SequenceService` provides auto-increment integers. It is useful when the `_id` of a model must be an integer.

Each sequence is a document in the `mongolid_sequences` collection of the default database. The document `_id` is the sequence name and the `seq` field holds the last value.

```php title="Getting the next value"
    use Mongolid\Container\Container;
    use Mongolid\Util\SequenceService;

    $sequences = Container::make(SequenceService::class);

    $sequences->getNextValue('orders'); // 1
    $sequences->getNextValue('orders'); // 2
```

- `getNextValue` creates the sequence when it does not exist. The first value is `1`.
- Pass a collection name as the second constructor argument to use a different collection.

## In a Schema

On `LegacyRecord` and `Schema` classes, a field with the `sequence` type gets the next value of the sequence when its value is empty. See [Schema](legacy/schema.md).
