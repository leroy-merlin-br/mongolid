---
sidebar_position: 10
---

# Events

Mongolid fires events around every write operation. By default nothing listens to them. To receive them, implement `Mongolid\Event\EventTriggerInterface` and register it.

```php title="Registering an event trigger"
use Mongolid\Connection\Connection;
use Mongolid\Connection\Manager;
use Mongolid\Event\EventTriggerInterface;

class Dispatcher implements EventTriggerInterface
{
    public function fire(string $event, $payload, bool $halt)
    {
        // Call your listeners here
        return true;
    }
}

$manager = new Manager();
$manager->setConnection(new Connection('mongodb://localhost:27017'));
$manager->setEventTrigger(new Dispatcher());
```

If you do not register a trigger, events are ignored and treated as successful.

## Event names

The `$event` argument is `mongolid.<event>: <model class>`, for example `mongolid.saving: Post`. The `$payload` is the model.

| Operation | Before | After |
|---|---|---|
| `save()` | `saving` | `saved` |
| `insert()` | `inserting` | `inserted` |
| `update()` | `updating` | `updated` |
| `delete()` | `deleting` | `deleted` |

## Cancelling an operation

The events fired before the write (`saving`, `inserting`, `updating`, `deleting`) are fired with `$halt` set to `true`. When your trigger returns `false` for one of them, the operation is cancelled and the method returns `false`.

## Things to know

- The "after" events are fired only when the write succeeds.
- With an unacknowledged write concern, the "after" events are not fired.
- When `update()` is called on a model without `_id`, Mongolid inserts it and fires `updating` and `updated`, but not `inserting` or `inserted`.
- A soft delete is an `update()`, so `delete()` on a model with `SoftDeleteTrait` fires `updating` and `updated`. See [Soft delete](soft-delete.md).
