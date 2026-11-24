---
sidebar_position: 10
---

# Events

Mongolid fires events around every write operation. By default nothing listens to them. Register an event trigger to handle them.

## Registering a trigger

Implement `Mongolid\Event\EventTriggerInterface` and pass it to `Manager::setEventTrigger`:

```php title="config"
use Mongolid\Connection\Connection;
use Mongolid\Connection\Manager;
use Mongolid\Event\EventTriggerInterface;

class MyEventTrigger implements EventTriggerInterface
{
    public function fire(string $event, $payload, bool $halt)
    {
        // $payload is the model
        return true;
    }
}

$manager = new Manager();
$manager->setConnection(new Connection('mongodb://localhost:27017'));
$manager->setEventTrigger(new MyEventTrigger());
```

## Event names

The event name is `mongolid.<event>: <model class>`, for example `mongolid.saving: Post`.

| Operation | Before | After |
|---|---|---|
| `save` | `saving` | `saved` |
| `insert` | `inserting` | `inserted` |
| `update` | `updating` | `updated` |
| `delete` | `deleting` | `deleted` |

## Cancelling an operation

The "before" events are fired with `$halt` set to `true`. If your `fire` method returns `false` for one of them, the operation is cancelled and the method (`save`, `insert`, `update` or `delete`) returns `false`.

## Edge cases

- When no trigger is registered, events are ignored and operations are not cancelled.
- "After" events are fired only when the write succeeds. With an unacknowledged write concern, the method always returns `false` and the "after" event is not fired.
- When `update` runs on a model without an `_id`, it inserts the model. It fires `updating` and `updated`, but not `inserting` or `inserted`.
