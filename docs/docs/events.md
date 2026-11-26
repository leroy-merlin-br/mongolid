---
sidebar_position: 11
---

# Events

Mongolid fires events when you save, insert, update, or delete a model. You can listen to them to run code before or after these operations, or to cancel them.


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

## Before and after events

- **Before events** (`saving`, `inserting`, `updating`, `deleting`) run before the database write. They receive `$halt = true`. If `fire()` returns `false`, the operation stops, nothing is written, and the method returns `false`. Use them to validate or cancel.
- **After events** (`saved`, `inserted`, `updated`, `deleted`) run only after the write succeeded. They receive `$halt = false`, and their return value is ignored. Use them to react, for example to send a notification or clear a cache.

## When events fire

- `update()` on a model without an `_id` inserts the document. It fires `updating` and `updated`, not `inserting` and `inserted`.
- `updated` fires only when the document was changed.
- `saved` fires when the document was modified or inserted. `inserted` fires only when the document was inserted.
- `deleted` fires only when a document was removed.
- With an unacknowledged write concern, the "after" events do not fire.
- Soft delete calls `update()`, so it fires `updating` and `updated`. See [Soft delete](./soft-delete.md#events).

## Models and DataMapper

`AbstractModel` and `LegacyRecord` fire the same events, with the model as the payload. The `DataMapper` fires them with the entity as the payload.

## Setting up a custom listener

Mongolid fires the events, but it cannot run any listener by itself. You are responsible for configuring the listeners. To connect them, provide an object that implements `Mongolid\Event\EventTriggerInterface`. Mongolid calls that object for every event, and it is up to you to forward the event to your listeners. The example below, `EventTrigger`, is a minimal implementation. Adapt it to your application, for example by forwarding the events to your framework's dispatcher.

### Implementing the EventTrigger

Create a class that implements `EventTriggerInterface`. Mongolid calls its `fire()` method for every event. The following class is a minimal example that cancels one case:

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

The `EventTrigger` above does not dispatch events to any listener. It only decides whether a `saving` event, fired by `save()`, is allowed to continue:

- For a `Post` with an empty `title`, it returns `false`, which cancels the save.
- For every other event, it returns `true`. Events that are not cancelable (`halt` is `false`) ignore the return value.

In a real application, forward each event to your own dispatcher and return its result. The example below uses a hypothetical `Dispatcher` class. Replace it with your application's dispatcher:

```php title="EventTrigger.php"
use Mongolid\Event\EventTriggerInterface;

class EventTrigger implements EventTriggerInterface
{
    protected $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function fire(string $event, $payload, bool $halt)
    {
        // $event looks like "mongolid.saving: App\Post"
        [$name] = array_map('trim', explode(':', $event, 2));

        // Mongolid cancels a "before" event if this value is false.
        return $this->dispatcher->dispatch($name, $payload);
    }
}
```

Mongolid cancels a "before" event only when `fire()` returns exactly `false`. If your dispatcher returns `null`, the operation continues.

Mongolid calls `fire()` with:

- `$event`: the event name, followed by `: ` and the model class (for example `mongolid.saving: App\Post`).
- `$payload`: the model or entity being written.
- `$halt`: `true` for the events that can cancel the operation, and `false` for the others.

Your implementation decides what to do with the event. You can dispatch it to your own listeners, a PSR-14 dispatcher, or the event system of your framework.

If no trigger is registered, events are ignored.

### Registering the EventTrigger

Pass the object to `Manager::setEventTrigger()`. The manager wraps it in a `Mongolid\Event\EventTriggerService` and registers that service in the container, which is where the models and the `DataMapper` look for it. You do not need to register the interface in the container yourself.

```php title="config"
use Mongolid\Connection\Manager;

$manager = new Manager();
$manager->setEventTrigger(new EventTrigger());
```

### Laravel

With Laravel, use the [mongolid-laravel](https://github.com/leroy-merlin-br/mongolid-laravel) package. It provides `MongolidLaravel\LaravelEventTrigger`, which forwards Mongolid events to Laravel's event dispatcher. The package's service provider already binds `EventTriggerService` in the container. The example below shows that binding, which you can use as a reference if you configure it yourself:

```php title="app/Providers/AppServiceProvider.php"
use Mongolid\Event\EventTriggerService;
use MongolidLaravel\LaravelEventTrigger;

public function register()
{
    $this->app->singleton(
        EventTriggerService::class,
        function ($app) {
            $eventService = new EventTriggerService();
            $eventService->registerEventDispatcher(
                $app->make(LaravelEventTrigger::class)
            );

            return $eventService;
        }
    );
}
```
