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

In the example above, the `id` and `votes` attributes may **not** be mass assigned.
All other attributes will be mass assignable.

You can mass assign attributes using the `fill` static method:

```php title="Mass assigning attributes"
    $post = new Post;
    $post = $post->fill(['title' => 'Bacon'], $post);
    // or
    $post = Post::fill(['title' => 'Bacon'], $post);
```
