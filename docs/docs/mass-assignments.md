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

## Bypassing mass assignment protection

Use `forceFill` to set attributes ignoring `fillable` and `guarded`:

```php title="Forcing attributes"
    $post = new Post;
    $post->forceFill(['_id' => '4af9f23d8ead0e1d32000000', 'votes' => 10]);
```

## Null values

Setting an attribute to `null` removes it from the model. Reading an attribute that is not set returns `null`, and `isset($post->title)` is `false` for `null` values.

## Mutators

Set `protected $mutable = true;` to run accessor and mutator methods. The method name is `get` or `set` + the attribute name in StudlyCase + `DocumentAttribute`:

```php title="Defining mutators"
    class Post extends \Mongolid\Model\AbstractModel {

        protected $mutable = true;

        public function getTitleDocumentAttribute()
        {
            return strtoupper($this->getDocumentAttributes()['title'] ?? '');
        }

        public function setTitleDocumentAttribute($value)
        {
            return trim($value);
        }
    }
```

The set mutator receives the value and must return the value to store.

## Polymorphic models

If a model implements `Mongolid\Model\PolymorphableModelInterface`, `fill` calls its `polymorph(array $input): string` method with the current attributes merged with the input. When it returns a different class, `fill` returns an instance of that class with the same attributes.

```php
    public function polymorph(array $input): string
    {
        return 'video' === ($input['type'] ?? '') ? VideoContent::class : Content::class;
    }
```
