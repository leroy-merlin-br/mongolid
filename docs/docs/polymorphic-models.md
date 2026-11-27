---
id: polymorphic-models
sidebar_position: 14
---

# Polymorphic models

Polymorphic models let documents of the same collection be represented by different classes. For example, a `contents` collection can hold articles and videos, each one with its own class and behavior.

To make a model polymorphic, implement `Mongolid\Model\PolymorphableModelInterface` and its `polymorph(array $input): string` method. The method receives the attributes and returns the class that should represent them.

## Configuring

Create a base model that implements the interface, and one subclass for each specialization. All of them share the collection of the base model.

```php title="Base model"
    use Mongolid\Model\AbstractModel;
    use Mongolid\Model\PolymorphableModelInterface;

    class Content extends AbstractModel implements PolymorphableModelInterface
    {
        protected $collection = 'contents';

        protected $fillable = ['title', 'type'];

        public function polymorph(array $input): string
        {
            return match ($input['type'] ?? null) {
                'video' => VideoContent::class,
                'article' => ArticleContent::class,
                default => static::class,
            };
        }
    }
```

```php title="Specializations"
    class ArticleContent extends Content
    {
        protected $fillable = ['title', 'type', 'body'];
    }

    class VideoContent extends Content
    {
        protected $fillable = ['title', 'type', 'url', 'duration'];
    }
```

`polymorph` must always return a class name. Return `static::class` to keep the current class, as in the `default` branch above.

## Using

`fill` calls `polymorph` before assigning the input. When the returned class is different from the class of the model, `fill` returns an instance of the returned class instead.

```php title="Filling a polymorphic model"
    $content = Content::fill([
        'type' => 'video',
        'title' => 'Mongolid tutorial',
        'url' => 'https://example.com/video',
    ]);

    $content instanceof VideoContent; // true
    $content->url; // 'https://example.com/video'

    $content = Content::fill(['type' => 'article', 'title' => 'Hello']);
    $content instanceof ArticleContent; // true

    $content = Content::fill(['type' => 'podcast', 'title' => 'Hello']);
    $content instanceof Content; // true, because polymorph returned static::class
```

You can also pass an existing model as the second argument. The current attributes of the model are merged with the input before `polymorph` is called, and the input has priority.

```php title="Filling an existing model"
    $content = new Content();
    $content->type = 'video';

    // 'type' comes from the model, so the result is a VideoContent
    $video = Content::fill(['url' => 'https://example.com/video'], $content);
```

`static::class` inside `polymorph` is the class of the model that is being filled. If you fill a `VideoContent` and `polymorph` returns `static::class`, the model stays a `VideoContent`.

## How the new instance is built

When `polymorph` returns a different class, `fill`:

1. Creates a new instance with `new $class()`, so the constructor must work without arguments.
2. Copies every attribute of the original model to the new instance, ignoring `fillable` and `guarded`.
3. Assigns the input to the new instance, respecting the `fillable` and `guarded` of the **new** class.

In the example above, `url` is assigned to a `VideoContent` because it is in its `fillable`. If you sent `url` together with `'type' => 'article'`, it would be ignored, because `ArticleContent` does not list it.

The original model is not changed. Use the object returned by `fill`.

:::warning
`forceFill` always returns the model it was called on, so it never changes the class. If you need the polymorphic behavior, use `fill`.
:::

## Schema entities

For entities built from a [Schema](schema.md), the data loaded from the database is also passed to `polymorph`. When it returns a different class, an instance of that class is created and receives the attributes with `fill($attributes, true)`.

See also [Mass assignment](mass-assignments.md) for `fillable` and `guarded`.
