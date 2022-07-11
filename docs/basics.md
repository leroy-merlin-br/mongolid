## Installation

You can install library through Composer:

```shell
composer require leroy-merlin-br/mongolid
```

### Requirements

- PHP **7.1**
- [MongoDB Driver](http://php.net/manual/en/set.mongodb.php)

## Setup

If you are not using Laravel, you should initialize the Mongolid connection and container manually.
The minimalistic way of doing it is to use `Mongolid\Connection\Manager`:

```php
<?php
require 'vendor/autoload.php';

use Mongolid\Connection\Manager;
use Mongolid\Connection\Connection;

$manager = new Manager();
$manager->setConnection(new Connection('mongodb://localhost:27017'));
```

Now you are ready to create your own models :smile:

## Basic usage

```php
class Post extends \Mongolid\Model\AbstractModel
{
}
```

Note that we did not tell Mongolid which collection to use for our `Post` model. So, in this case, Mongolid **will not save the model into the database**. 
This can be used for models that represents objects that will be embedded within another object and will not have their own collection.

You may specify a collection by defining a `collection` property on your model:

```php
class Post extends \Mongolid\Model\AbstractModel {

    protected $collection = 'posts';

}
```

Mongolid will also assume each collection has a primary key attribute named `_id`, since MongoDB requires an `_id` for every single document. 
The `_id` attribute can be of any type. The default type for this attribute is `ObjectId`. 
[Learn more about the MongoId](https://docs.mongodb.org/manual/reference/method/ObjectId/).

> **Note:** Mongolid will automatically convert strings in ObjectId format (For example: "4af9f23d8ead0e1d32000000") 
> to `ObjectId` when querying or saving an object.

Once a model is defined, you are ready to start retrieving and creating documents in your collection.

#### Retrieving all models

```php
$posts = Post::all();
```

#### Retrieving a document by primary key

```php
$post = Post::first('4af9f23d8ead0e1d32000000');

// or

$post = Post::first(new MongoDB\BSON\ObjectID('4af9f23d8ead0e1d32000000'));
```

#### Retrieving a document by attribute

```php
$user = Post::first(['title' => 'How Mongolid saved the day']);
```

#### Retrieving many documents by attribute

```php
$posts = Post::where(['category' => 'coding']); // where() method returns a MongolidCursor
```

#### Querying using mongolid models

```php
$posts = Post::where(['votes' => ['$gt' => 100]])->limit(10); // Mongolid\Cursor\Cursor

foreach ($posts as $post) {
    var_dump($post->title);
}
```

#### Mongolid count

```php
$count = Post::where(['votes' => ['$gt' => 100]])->count(); // int
```

Pretty easy right?

## Mongolid cursor

Learn more about [Mongolid Cursor](cursor.md)

## Insert, Update, Delete

To create a new document in the database from a model, simply create a new model instance and call the `save` method.

#### Saving a new mode

```php
$post = new Post();

$post->title = 'Foo bar john doe';

$post->save();
```

> **Note:** Typically, your Mongolid models will have auto-generated `_id` keys. 
> However, if you wish to specify your own keys, set the `_id` attribute.

To update a model, you may retrieve it, change an attribute, and use the `save` method:

#### Updating a retrieved model

```php
$post = Post::first('4af9f23d8ead0e1d32000000');

$post->subject = 'technology';

$post->update();
```

To delete a model, simply call the `delete` method on the instance:

#### Deleting an existing model

```php
$post = Post::first('4af9f23d8ead0e1d32000000');

$post->delete();
```

## Mass assignment

If you are extending `Mongolid\Model\AbstractModel` you can set an array of attributes to the model using the `fill` method. 
These attributes are then assigned to the model via mass-assignment. 
This is convenient; however, can be a **serious** security concern when blindly passing user input into a model. 
If user input is blindly passed into a model, the user is free to modify **any** and **all** of the model's attributes. 
By default, all attributes are fillable.

`Mongolid\Model\AbstractModel` (and `Mongolid\Model\HasAttributesTrait`) will use the `fillable` or `guarded` properties on your model.

The `fillable` property specifies which attributes should be mass-assignable. This can be set at the class or instance level.

#### Defining fillable attributes on a model

```php
class Post extends \Mongolid\Model\AbstractModel {

    protected $fillable = ['title', 'category', 'body'];

}
```

In this example, only the three listed attributes will be mass-assignable.

The inverse of `fillable` is `guarded`, and serves as a "black-list" instead of a "white-list":

#### Defining guarded attributes on a model

```php
class Post extends \Mongolid\Model\AbstractModel {

    protected $guarded = ['_id', 'votes'];

}
```

In the example above, the `id` and `votes` attributes may **not** be mass assigned. 
All other attributes will be mass assignable.

You can mass assign attributes using the `fill` static method:

```php
$post = new Post;
$post = $post::fill(['title' => 'Bacon'], $post);
// or
$post = Post::fill(['title' => 'Bacon'], $post);

```

## Converting to arrays

When building JSON APIs, you may often need to convert your models to arrays. So, Mongolid includes methods for doing so. 
To convert a model and its loaded relationship to an array, you may use the `toArray` method:

#### Converting a model to an array

```php
$user = User::first();

return $user->toArray();
```

Note that [cursors](cursor.md) can be converted to array too:

```php
return User::all()->toArray();
```

#### Converting a model to json

This resource is still present only on `LegacyRecord`. 

You can see here [LegacyRecord](legacy/record.md) *For compatibility with version 2.x*


## More about

- [Relationships](relationships.md)
- [Troubleshooting](troubleshooting.md)
