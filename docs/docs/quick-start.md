---
sidebar_position: 1
---

# Quick Start

:::info Requirements
- PHP **7.1**
- [MongoDB Driver](http://php.net/manual/en/set.mongodb.php)
:::

## Installation

You can install library through Composer:

```shell title="command"
composer require leroy-merlin-br/mongolid
```

## Setup

If you are not using Laravel, you should initialize the Mongolid connection and container manually.
The minimalistic way of doing it is to use `Mongolid\Connection\Manager`:

```php title="config"
require 'vendor/autoload.php';

use Mongolid\Connection\Manager;
use Mongolid\Connection\Connection;

$manager = new Manager();
$manager->setConnection(new Connection('mongodb://localhost:27017'));
```

Now you are ready to create your own models :smile:

## Basic usage

```php title="Post.php"
class Post extends \Mongolid\Model\AbstractModel
{
}
```

Note that we did not tell Mongolid which collection to use for our `Post` model. So, in this case, Mongolid **will not save the model into the database**.
This can be used for models that represents objects that will be embedded within another object and will not have their own collection.

You may specify a collection by defining a `collection` property on your model:

```php title="Post.php"
class Post extends \Mongolid\Model\AbstractModel {

    protected $collection = 'posts';

}
```

Mongolid will also assume each collection has a primary key attribute named `_id`, since MongoDB requires an `_id` for every single document.
The `_id` attribute can be of any type. The default type for this attribute is `ObjectId`.
[Learn more about the MongoId](https://docs.mongodb.org/manual/reference/method/ObjectId/).

:::info
Mongolid will automatically convert strings in ObjectId format (For example: "4af9f23d8ead0e1d32000000")
to `ObjectId` when querying or saving an object.
:::

Once a model is defined, you are ready to start retrieving and creating documents in your collection.

```php title="Retrieving all models"
    $posts = Post::all();
```

```php title="Retrieving a document by primary key"
    $post = Post::first('4af9f23d8ead0e1d32000000');

    // or
    
    $post = Post::first(new MongoDB\BSON\ObjectID('4af9f23d8ead0e1d32000000'));
```

```php title="Retrieving a document by attribute"
    $post = Post::first(['title' => 'How Mongolid saved the day']);
```

```php title="Retrieving many documents by attribute"
    $posts = Post::where(['category' => 'coding']); // where() method returns a MongolidCursor
```

```php title="Querying using mongolid models"
    $posts = Post::where(['votes' => ['$gt' => 100]])->limit(10); // Mongolid\Cursor\Cursor

    foreach ($posts as $post) {
        var_dump($post->title);
    }
```

```php title="Mongolid count"
    $count = Post::where(['votes' => ['$gt' => 100]])->count(); // int
```

---

***Pretty easy right?***
