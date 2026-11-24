---
id: mass-assignments
sidebar_position: 4
---

# Mass assignment

If you are extending `Mongolid\Model\AbstractModel` you can set an array of attributes to the model using the `fill` method.
These attributes are then assigned to the model via mass-assignment.

This is convenient, however, can be a **serious** security concern when blindly passing user input into a model.
If user input is blindly passed into a model, the user is free to modify **any** and **all** the model's attributes.
By default, all attributes are fillable.

`Mongolid\Model\AbstractModel` (and `Mongolid\Model\HasAttributesTrait`) will use the `fillable` or `guarded` properties on your model.

The `fillable` property specifies which attributes should be mass-assignable. This can be set at the class or instance level.

```php title="Defining fillable attributes on a model"
    class Post extends \Mongolid\Model\AbstractModel {
    
        protected $fillable = ['title', 'category', 'body'];
    
    }
```

In this example, only the three listed attributes will be mass-assignable.

The inverse of `fillable` is `guarded`, and serves as a "black-list" instead of a "white-list":

```php title="Defining guarded attributes on a model"
    class Post extends \Mongolid\Model\AbstractModel {
    
        protected $guarded = ['_id', 'votes'];
    
    }
```

In the example above, the `_id` and `votes` attributes may **not** be mass assigned.
All other attributes will be mass assignable.

You can mass assign attributes using the static `fill` method. It receives the input array, an optional model instance to fill and an optional `$force` flag. When no instance is given, a new one is created:

```php title="Mass assigning attributes"
    $post = new Post;
    $post = Post::fill(['title' => 'Bacon']);
    // or fill an existing instance
    $post = Post::fill(['title' => 'Bacon'], new Post);
```

## Polymorphic models

Implement `Mongolid\Model\PolymorphableModelInterface` to let `fill` choose the class of the model. The `polymorph` method receives the current attributes merged with the input, and returns the class to instantiate.

```php title="Choosing the class on fill"
    class Content extends \Mongolid\Model\AbstractModel implements \Mongolid\Model\PolymorphableModelInterface
    {
        protected $collection = 'contents';

        public function polymorph(array $input): string
        {
            return ($input['type'] ?? '') === 'video' ? VideoContent::class : static::class;
        }
    }

    $content = Content::fill(['type' => 'video']); // VideoContent
```

When the returned class differs from the current one, Mongolid creates a new instance of it and copies the existing attributes before applying the input.

## Bypassing mass assignment protection

Use `forceFill` to set attributes ignoring `fillable` and `guarded`:

```php title="Forcing attributes"
    $post = new Post;
    $post->forceFill(['_id' => '4af9f23d8ead0e1d32000000', 'votes' => 10]);
```
