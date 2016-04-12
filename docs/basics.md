## Installation

You can install library through Composer:

```
$ composer require leroy-merlin-br/mongolid
```

### Requirements

- PHP**7**
- [MongoDB Driver](http://php.net/manual/en/set.mongodb.php)

> **Note:** If you are looking for the old PHP 5.x version, head to the [v0.8 branch](https://github.com/leroy-merlin-br/mongolid/tree/v0.8-dev).

## Setup

If you are not using Laravel, you should initialize the Mongolid connection pool and container manually.
The minimalistic way of doing it is to use `Mongolid\Manager`:

```php
<?php
require 'vendor/autoload.php';

use Mongolid\Manager;
use Mongolid\Connection;

$manager = new Manager(new Connection('mongodb://localhost:27017'));
```

Now you are ready to create your own models :smile:

## Basic Usage

> **Note:** Mongolid does support [**DataMapper** pattern](./datamapper.md), but in order to understand it let's begin with the **ActiveRecord** pattern:

```php
class Post extends Mongolid\ActiveRecord {}
```

Note that we did not tell Mongolid which collection to use for our `Post` model. So, in this case, Mongolid **will not save the model into the database**. This can be used for models that represents objects that will be embedded within another object and will not have their own collection.

You may specify a collection by defining a `collection` property on your model:

```php
class Post extends ActiveRecord {

    protected $collection = 'posts';

}
```

Mongolid will also assume each collection has a primary key attribute named `_id`, since MongoDB requires an `_id` for every single document. The `_id` attribute can be of any type. The default type for this attribute is `ObjectId`. [Learn more about the MongoId](https://docs.mongodb.org/manual/reference/method/ObjectId/).

> **Note:** Mongolid will automatically convert strings in ObjectId format (For example: "4af9f23d8ead0e1d32000000") to `ObjectId` when querying or saving an object.

Once a model is defined, you are ready to start retrieving and creating documents in your collection.

**Retrieving All Models**

```php
    $posts = Post::all();
```

**Retrieving A Document By Primary Key**

```php
    $post = Post::first('4af9f23d8ead0e1d32000000');

    // or

    $post = Post::first(new MongoDB\BSON\ObjectID('4af9f23d8ead0e1d32000000'));
```

**Retrieving One Document By attribute**

```php
    $user = Post::first(['title'=>'How Monglid saved the day']);
```

**Retrieving Many Documents By attribute**

```php
    $posts = Post::where(['category'=>'coding']);
```

**Querying Using Mongolid Models**

```php
    $posts = Post::where(['votes'=>['$gt'=>100]])->limit(10); // Mongolid\Cursor\Cursor

    foreach ($posts as $post)
    {
        var_dump($post->title);
    }
```

**Mongolid Count**

```php
    $count = Post::where(['votes'=>['$gt'=>100]])->count(); // integer
```

Pretty easy right?

## Monglid Cursor

In MongoDB, a cursor is used to iterate through the results of a database query. For example, to query the database and see all results:

```php
    $cursor = User::where(['kind'=>'visitor']);
```

In the above example, the $cursor variable will be a `Mongolid\Cursor\Cursor`.

The Mongolid's `Cursor` wraps the original `MongoDB\Driver\Cursor` object of the new MongoDB Driver in a way that you can build queries in a more fluent and easy way. Also the Mongolid's `Cursor` will make sure to return the instances of your model instead of stdClass or arrays.

> **Note:** The [Cursor class of the new driver](http://php.net/manual/en/class.mongodb-driver-cursor.php) is not as user friendly as the old one. Mongolid's cursor also make it as easy to use as the old one.

The `Mongolid\Cursor\Cursor` object has alot of methods that helps you to iterate, refine and get information. For example:

```php
    $cursor = User::where(['kind'=>'visitor']);

    // Sorts the results by given fields. In the example bellow, it sorts by username DESC
    $cursor->sort(['username'=>-1]);

    // Limits the number of results returned.
    $cursor->limit(10);

    // Skips a number of results. Good for pagination
    $cursor->skip(20);

    // Checks if the cursor is reading a valid result.
    $cursor->valid();

    // Returns the first result
    $cursor->first();
```

You can also chain some methods:

```php
    $page = 2;

    // In order to display 10 results per page
    $cursor = User::all()->sort(['_id'=>1])->skip(10 * $page)->limit(10);

    // Then iterate through it
    foreach($cursor as $user) {
        // do something
    }
```

## Insert, Update, Delete

To create a new document in the database from a model, simply create a new model instance and call the `save` method.

**Saving A New Model**

```php
    $post = new Post;

    $post->title = 'Foo bar john doe';

    $post->save();
```

> **Note:** Typically, your Mongolid models will have auto-generated `_id` keys. However, if you wish to specify your own keys, set the `_id` attribute.

To update a model, you may retrieve it, change an attribute, and use the `save` method:

**Updating A Retrieved Model**

```php
    $post = Post::first('4af9f23d8ead0e1d32000000');

    $post->subject = 'technology';

    $post->save();
```

To delete a model, simply call the `delete` method on the instance:

**Deleting An Existing Model**

```php
    $post = Post::first('4af9f23d8ead0e1d32000000');

    $post->delete();
```

## Mass Assignment

If you are extending `Mongolid\ActiveRecord` you can set an array of attributes to the model using the `fill` method. These attributes are then assigned to the model via mass-assignment. This is convenient; however, can be a **serious** security concern when blindly passing user input into a model. If user input is blindly passed into a model, the user is free to modify **any** and **all** of the model's attributes. By default, all attributes are fillable.

`Mongolid\ActiveRecord` (and `Mongolid\Model\Attributes` trait) will use the `fillable` or `guarded` properties on your model.

The `fillable` property specifies which attributes should be mass-assignable. This can be set at the class or instance level.

**Defining Fillable Attributes On A Model**

```php
    class Post extends ActiveRecord {

        protected $fillable = ['title', 'category', 'body'];

    }
```

In this example, only the three listed attributes will be mass-assignable.

The inverse of `fillable` is `guarded`, and serves as a "black-list" instead of a "white-list":

**Defining Guarded Attributes On A Model**

```php
    class Post extends ActiveRecord {

        protected $guarded = ['_id', 'votes'];

    }
```

In the example above, the `id` and `votes` attributes may **not** be mass assigned. All other attributes will be mass assignable.

You can mass assign attributes using the `fill` method:

```php
    $post = new Post;
    $post->fill(['title' => 'Bacon']);
```

## Converting To Arrays / JSON

When building JSON APIs, you may often need to convert your models to arrays or JSON. So, Mongolid includes methods for doing so. To convert a model and its loaded relationship to an array, you may use the `toArray` method:

**Converting A Model To An Array**

```php
    $user = User::with('roles')->first();

    return $user->toArray();
```

Note that [cursors](#cursor) can be converted to array too:

```php
    return User::all()->toArray();
```

To convert a model to JSON, you may use the `toJson` method:

**Converting A Model To JSON**

```php
    return User::find(1)->toJson();
```
