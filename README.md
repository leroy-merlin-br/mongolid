# Mongolid ODM for MongoDB (PHP7)


> Easy, powerful and ultrafast ODM for PHP7 build on top of the [new mongodb driver](https://docs.mongodb.org/ecosystem/drivers/php/).

![Mongolid](https://dl.dropboxusercontent.com/u/12506137/libs_bundles/mongolid_banner.png)

Mongolid supports both **ActiveRecord** and **DataMapper** patterns. **You choose! (:**

[![Build Status](https://travis-ci.org/leroy-merlin-br/mongolid.svg?branch=master)](https://travis-ci.org/leroy-merlin-br/mongolid)
[![Coverage Status](https://coveralls.io/repos/github/leroy-merlin-br/mongolid/badge.svg?branch=v2.0.0)](https://coveralls.io/github/leroy-merlin-br/mongolid?branch=v2.0.0)
[![Latest Stable Version](https://poser.pugx.org/zizaco/mongolid/v/stable.png)](https://packagist.org/packages/zizaco/mongolid)
[![Total Downloads](https://poser.pugx.org/zizaco/mongolid/downloads.png)](https://packagist.org/packages/zizaco/mongolid)
[![Latest Unstable Version](https://poser.pugx.org/zizaco/mongolid/v/unstable.png)](https://packagist.org/packages/zizaco/mongolid)
[![License](https://poser.pugx.org/zizaco/mongolid/license.png)](https://packagist.org/packages/zizaco/mongolid)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/25636a94-9a5d-4438-bd5e-9f9694104529/small.png)](https://insight.sensiolabs.com/projects/25636a94-9a5d-4438-bd5e-9f9694104529)

<a name="introduction"></a>
## Introduction

Mongolid ODM (Object Document Mapper) provides a beautiful, simple implementation for working with MongoDB. Each database collection can have a corresponding "Model" which is used to interact with that collection.

> **Note:** If you are working with Laravel, take a look at [mongolid-laravel repository](https://github.com/leroy-merlin-br/mongolid-laravel).

<a name="installation"></a>
## Installation

You can install library through Composer:

```
$ composer require leroy-merlin-br/mongolid
```

<a name="basic-usage"></a>
## Basic Usage

First of all you should initialize the Mongolid connection pool. The minimalistic way of doing it is to use `Mongolid\Manager`:

```php
<?php
require 'vendor/autoload.php';

use Mongolid\Manager;
use Mongolid\Connection;

$manager = new Manager(new Connection('mongodb://localhost:27017'));
```

Now you are ready to create your own models. Let's begin with the **ActiveRecord** pattern:

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

<a name="cursor"></a>
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

<a name="insert-update-delete"></a>
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

<a name="mass-assignment"></a>
## Mass Assignment

If you are extending `Mongolid\ActiveRecord` you can set an array of attributes to the model constructor. These attributes are then assigned to the model via mass-assignment. This is convenient; however, can be a **serious** security concern when blindly passing user input into a model. If user input is blindly passed into a model, the user is free to modify **any** and **all** of the model's attributes. By default, all attributes are fillable.

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
    $post = new Post(['title' => 'Bacon']);

    // or

    $post = new Post;
    $post->fill(['title' => 'Bacon']);
```

<a name="relationships"></a>
## Relationships

Of course, your database collections are probably related to one another. For example, a blog post may have many comments, or an order could be related to the user who placed it. Mongolid makes managing and working with these relationships easy. MongoDB and Mongolid in short supports four types of relationships:

- [Embeds One](#embeds-one)
- [Embeds Many](#embeds-many)
- [References One](#references-one)
- [References Many](#references-many)

> **Note:** MongoDB **relationships doesn't works like in a Relational database**. In MongoDB, data modeling decisions involve determining how to structure the documents to model the data effectively. The primary decision is whether to embed or to use references. See [MongoDB - Data Modeling Decisions](https://docs.mongodb.org/manual/core/data-model-design/) for more information on this subject.

<a name="embeds-one"></a>
### Embeds One

Read [MongoDB - Embedded Data Models](https://docs.mongodb.org/manual/core/data-model-design/#embedded-data-models) to learn more how to take advantage of document embedding.

A Embeds One relationship is a very basic relation. For example, a `User` model might have one `Phone`. We can define this relation in Mongolid:

**Defining A Embeds One Relation**

```php
    // models/Person.php
    class Person extends ActiveRecord {

        // This model is saved in the collection people
        protected $collection = 'people';

        // Method that will be used to access the phone
        public function phone()
        {
            return $this->embedsOne('Phone', 'phone');
        }

    }

    // models/Phone.php
    class Phone extends ActiveRecord {

        // This model will be embedded only
        protected $collection = null;

        public function getFullPhone()
        {
            return '+' . $this->regionCode . $this->number;
        }

    }
```

The first argument passed to the `embedsOne` method is the name of the related model. The second argument is in what attribute that object will be embedded. Once the relationship is defined, we may retrieve it using:

```php
    $phone = User::find('4af9f23d8ead0e1d32000000')->phone();
```

Which will translate to:

- Query for the user with the `_id` _'4af9f23d8ead0e1d32000000'_
- Instantiate a **Phone** object with the attributes found in _'phone'_ attribute of the user
- Return that object

In order to embed a document to be used in a Embeds One relationship, simply fo the following:

```php
    // The object that will be embeded
    $phoneObj = new Phone;
    $phoneObj->regionCode = '55';
    $phoneObj->number = '1532323232';

    // The object that will contain the phone
    $user = User::first('4af9f23d8ead0e1d32000000');

    // This method will embed the $phoneObj into the phone attribute of the user
    $user->embed('phone', $phoneObj);

    // This is an alias to the method called above.
    $user->embedToPhone($phoneObj);

    // Not recomended, but also works
    $user->phone = $phoneObj->attributes;

    // Or (not recomended)
    $user->phone = $phoneObj->toArray();

    // Or even (not recomended)
    $user->phone = [
        'regionCode' => $phoneObj->regionCode,
        'number' => $phoneObj->number
    ];

    $user->save();

    // Now we can retrieve the object by calling
    $user->phone(); // Will return a Phone object similar to $phoneObj
```

> **Note:** When using Mongolid models you will need to call the `save()` method after embeding or attaching objects. The changes will only persists after you call the 'save()' method.

It's recomended that you don't embed your models by setting the attribute directly. The `embed` method will include an `_id` to identify your embeded document and allow the usage of `unembed` and `embed` to update models.

```php
    $user->embed('phone', $phoneObj); // Now, $phoneObj have an _id

    $phoneObj->regionCode = 77; // Update the region code
    
    $user->embed($phoneObj); // Will update
```

<a name="embeds-many"></a>
### Embeds many

An example of a Embeds Many relation is a blog post that "has many" comments. We can model this relation like so:

```php
    // models/Post.php
    class Post extends ActiveRecord {

        protected $collection = 'posts';

        public function comments()
        {
            return $this->embedsMany('Comment', 'comments');
        }

    }

    // models/Comment.php
    class Comment extends Mongolid{

        // This model will be embedded only
        protected $collection = null;

    }
```

Now we can access the post's comments `EmbeddedCursor` through the comments method:

```php
    $comments = Post::find('4af9f23d8ead0e1d32000000')->comments();
```

Now you can iterate and perform cursor operations in the `EmbeddedCursor` that is retrieved

```php
    foreach($comments->limit(10) as $comment)
    {
        // do something
    }
```

In order to embed a document to be used in a Embeds Many relationship, you should use the `embed` method or the alias `embedTo<Attribute>`:

```php
    $commentA = new Comment;
    $commentA->content = 'Cool feature bro!';

    $commentB = new Comment;
    $commentB->content = 'Awesome!';

    $post = Post::first('4af9f23d8ead0e1d32000000');

    // Both ways work
    $post->embedToComments($commentA);
    $post->embed('Comments', $commentB);

    $post->save();
```

> **Note:** When using Mongolid models you will need to call the `save()` method after embeding or attaching objects. The changes will only persists after you call the 'save()' method.

The `embed` method will include an `_id` to identify your embeded document and allow the usage of `embed` and `unembed` to update or delete embeded documents:

```php
    $commentB->content = "Pretty awesome!";

    $post->unembed($commentA); // Removes 'Cool feature bro!'
    $post->embed($commentB);   // Updates 'Awesome' to 'Pretty awesome'
```

<a name="references-one"></a>
### References One

In Mongolid a reference is made by storing the `_id` of the referenced object. 

Referencing provides more flexibility than embedding; however, to resolve the references, client-side applications must issue follow-up queries. In other words, using references requires more roundtrips to the server.

In general, use references when embedding would result in duplication of data and would not provide sufficient read performance advantages to outweigh the implications of the duplication. Read [MongoDB - Relationships with Document References](https://docs.mongodb.org/manual/tutorial/model-referenced-one-to-many-relationships-between-documents/) to learn more how to take advantage of referencing in MongoDB.

> **Note:** MongoDB **relationships doesn't works like in a Relational database**. In MongoDB, data modeling decisions involve determining how to structure the documents to model the data effectively. If you try to create references between documents like you would do in a relational database you will end up with _"n+1 problem"_ and poor performance.

**Defining A References One Relation**

```php
    // models/Post.php
    class Post extends ActiveRecord {

        protected $collection = 'posts';

        public function author()
        {
            return $this->referencesOne('User', 'author');
        }

    }

    // models/User.php
    class User extends Mongolid{

        protected $collection = 'users';

    }
```

The first argument passed to the `referencesOne` method is the name of the related model, the second argument is the attribute where the referenced model `_id` will be stored. Once the relationship is defined, we may retrieve it using the following method:

```php
    $user = Post::find('4af9f23d8ead0e1d32000000')->author();
```

This statement will perform the following:

- Query for the post with the `_id` _'4af9f23d8ead0e1d32000000'_
- Query for the user with the `_id` equals to the _author_ attribute of the post
- Return that object

In order to set a reference to a document, simply set the attribute used in the relationship to the reference's `_id` or use the attach method or it's alias. For example:

```php
    // The object that will be embeded
    $userObj = new User;
    $userObj->name = 'John';
    $userObj->save() // This will populates the $userObj->_id

    // The object that will contain the user
    $post = Post::first('4af9f23d8ead0e1d32000000');

    // This method will attach the $phoneObj _id into the phone attribute of the user
    $post->attach('author', $userObj);

    // This is an alias to the method called above.
    $post->attachToAuthor($userObj);

    // This will will also work
    $post->author = $userObj->_id;

    $post->save();

    $post->author(); // Will return a User object
```

> **Note:** When using Mongolid models you will need to call the `save()` method after embedding or attaching objects. The changes will only persists after you call the 'save()' method.

<a name="references-many"></a>
### References Many

In Mongolid a _References Many_ is made by storing the `_id`s of the referenced objects.

Referencing provides more flexibility than embedding; however, to resolve the references, client-side applications must issue follow-up queries. In other words, using references requires more roundtrips to the server.

In general, use references when embedding would result in duplication of data and would not provide sufficient read performance advantages to outweigh the implications of the duplication. Read [MongoDB - Relationships with Document References](https://docs.mongodb.org/manual/tutorial/model-referenced-one-to-many-relationships-between-documents/) to learn more how to take advantage of referencing in MongoDB.

**Defining A References Many Relation**

```php
    // models/User.php
    class User extends Mongolid{

        protected $collection = 'users';

        public function questions()
        {
            return $this->referencesMany('Question', 'questions');
        }

    }

    // models/Question.php
    class Question extends ActiveRecord {

        protected $collection = 'questions';

    }
```

The first argument passed to the `referencesMany` method is the name of the related model, the second argument is the attribute where the `_id`s will be stored. Once the relationship is defined, we may retrieve it using the following method:

```php
    $posts = User::find('4af9f23d8ead0e1d32000000')->posts();
```

This statement will perform the following:

- Query for the user with the `_id` _'4af9f23d8ead0e1d32000000'_
- Query for all the posts with the `_id` in the user's _posts_ attribute
- Return the [`Mongolid\Cursor\Cursor`](#cursor) with the related posts

In order to set a reference to a document use the attach method or it's alias. For example:

```php
    $postA = new Post;
    $postA->title = 'Nice post';

    $postB = new Post;
    $postB->title = 'Nicer post';

    $user = User::first('4af9f23d8ead0e1d32000000');

    // Both ways work
    $user->attachToPosts($postA);
    $user->attach('posts', $postB);

    $user->save();
```

> **Note:** When using Mongolid models you will need to call the `save()` method after embedding or attaching objects. The changes will only persists after you call the 'save()' method.

You can use `dettach` method with the referenced object or it's `_id` in order to remove a single reference.

<a name="converting-to-arrays-or-json"></a>
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

<a name="troubleshooting"></a>
## Troubleshooting

**"PHP Fatal error: Class 'MongoDB\Client' not found in ..."**

The `MongoDB\Client` class is contained in the [**new** MongoDB driver](http://pecl.php.net/package/mongodb) for PHP. [Here is an installation guide](http://www.php.net/manual/en/mongodb.installation.php). The driver is a PHP extension written in C and maintained by [MongoDB](https://mongodb.com). Mongolid and most other MongoDB PHP libraries utilize it in order to be fast and reliable.

**"Class 'MongoDB\Client' not found in ..." in CLI persists even with MongoDB driver installed.**

Make sure that the **php.ini** file used in the CLI environment includes the MongoDB extension. In some systems, the default PHP installation uses different **.ini** files for the web and CLI environments.

Run `php -i | grep 'Configuration File'` in a terminal to check the **.ini** that is being used.

To check if PHP in the CLI environment is importing the driver properly run `php -i | grep 'mongo'` in your terminal. You should get output similar to:

```
$ php -i | grep 'mongo'
mongodb support => enabled
mongodb version => 1.1.3
```

<a name="license"></a>
## License

Mongolid is free software distributed under the terms of the [MIT license](http://opensource.org/licenses/MIT)

<a name="additional_information"></a>
## Additional information

Any questions, feel free to contact me.

Any issues, please [report here](https://github.com/Zizaco/mongolid)
