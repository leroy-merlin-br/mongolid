---
sidebar_position: 11
---

# Events

Mongolid fires events when you save, insert, update, or delete a model. You can listen to them to run code before or after these operations, or to cancel them.

## Setting up a listener

Mongolid does not dispatch events itself. You provide an object that implements `Mongolid\Event\EventTriggerInterface`, and register it on the `Manager`:

```php title="EventTrigger.php"
use App\Post;
use Mongolid\Event\EventTriggerInterface;

class EventTrigger implements EventTriggerInterface
{
    public function fire(string $event, $payload, bool $halt)
    {
        // $event looks like "mongolid.saving: App\Post"
        [$name] = array_map('trim', explode(':', $event, 2));

        if ($name === 'mongolid.saving' && $payload instanceof Post && $payload->title === '') {
            return false;
        }

        return true;
    }
}
```

```php title="config"
$manager->setEventTrigger(new EventTrigger());
```

Mongolid calls `fire()` with:

- `$event`: the event name, followed by `: ` and the model class (for example `mongolid.saving: App\Post`).
- `$payload`: the model or entity being written.
- `$halt`: `true` for the events that can cancel the operation, and `false` for the others.

Your implementation decides what to do with the event. You can dispatch it to your own listeners, a PSR-14 dispatcher, or the event system of your framework.

If no trigger is registered, events are ignored.

## Available events

| Event | Fired by | Can cancel |
|---|---|---|
| `saving` | `save()` | Yes |
| `saved` | `save()` | No |
| `inserting` | `insert()` | Yes |
| `inserted` | `insert()` | No |
| `updating` | `update()` | Yes |
| `updated` | `update()` | No |
| `deleting` | `delete()` | Yes |
| `deleted` | `delete()` | No |

## Cancelling an operation

For the events marked "Can cancel", the operation stops if `fire()` returns `false`. The method then returns `false` and nothing is written.

The return value of the other events is ignored.

## When events fire

- The "before" events run before the database call. The "after" events run only if the operation succeeded.
- `update()` on a model without an `_id` inserts the document. It fires `updating` and `updated`, not `inserting` and `inserted`.
- `updated` fires only when the document was changed. Calling `update()` without changes does not fire it.
- `saved` fires when the document was modified or inserted. `inserted` fires only when the document was inserted.
- `deleted` fires only when a document was removed.
- With an unacknowledged write concern, the "after" events do not fire.
- Soft delete calls `update()`, so it fires `updating` and `updated`. See [Soft delete](./soft-delete.md#events).

## Models and DataMapper

`AbstractModel` and `LegacyRecord` fire the same events, with the model as the payload. The `DataMapper` fires them with the entity as the payload.
