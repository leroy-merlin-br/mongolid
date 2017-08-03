## Introduction

In the [Basics](./basics.md#basic-usage) section you learned how to use Mongolid with the ActiveRecord pattern. The following section you explain what changes are necessary in order to use the DataMapper pattern of Mongolid.

> **Note:** To use Mongolid in the DataMapper pattern is optional. If you are looking for a more [Domain Driven Design](https://en.wikipedia.org/wiki/Domain-driven_design) approach in your project it may be interesting to your. But if you are satisfied with what you've learned in the other sections, feel free to skip this one.

## Basics

First of all, you have to define a _Schema_ for your model. This is the way to map objects into the database. But don't worry, your schema can be dynamic, which means that you can define other fields than the specified ones.

> **Note:** The _Mongolid Schema_ is equivalent to [mapping your objects using annotation or xml](http://doctrine-orm.readthedocs.org/projects/doctrine-orm/en/latest/reference/xml-mapping.html) in other ORM or ODM libraries.

In order to define a schema you should extend `Mongolid\Schema\Schema` or `Mongolid\Schema\DynamicSchema`:

```php
<?php

class ArticleSchema extends Mongolid\Schema\Schema {

    public $entityClass = 'Article';

    public $collection = 'articles';

    public $fields  = [
        '_id'         => 'objectId',
        'title'       => 'string',
        'body'        => 'string',
        'views'       => 'int',
        'created_at'  => 'createdAtTimestamp',
        'updated_at'  => 'updatedAtTimestamp'
    ];
}
```

Then you should register an instance of the schema into the `Mongolid\Manager`:

```php
$manager->registerSchema(new ArticleSchema);
```

Now you just have to create your own Domain Entity (:

```php
class Article
{
    public $_id
    public $title
    public $body
    public $views
    public $created_at
    public $updated_at

    public function render() {
        return "# {$this->title}\n\n{$this->body}";
    }
}
```

## Interacting with the database

Interact with the database using the `DataMapper` retrieved through the Mongolid Manager:

```php
$article = $manager->getMapper(Article::class)
    ->where(['title' => 'Foobar'])->first();

get_class($article) // Article

$article->render() // # Foobar\n\nBody
$article->title = 'How Mongolid saved the day';

$manager->getMapper(Article::class)->save($article); // true
```

## Schema definition

When defining your schema you can eighter extend the `Mongolid\Schema\Schema` or `Mongolid\Schema\DynamicSchema`. The main difference is the `$dynamic` property value.

**Making a schema dynamic**

The `$dynamic` property is a `boolean` that tells if the schema will accept additional fields that are not specified in the $fields property. This is usefull if you doesn't have a strict document format or if you want to take full advantage of the "schemaless" nature of MongoDB.

**Defining the collection and the Domain Entity to be mapped**

`$collection` property should be the name of the collection where this kind of document is going to be saved or retrieved from. And the `$entityClass` should be the name of the class that will be used to represent a document of the Schema when retrieve from the database.

**Defining the fields**

The `$fields` property is an array that tells how a document should look like. For each field of the document you can specify a type or how it will be "formated".

If an scalar type is used, it will perform a cast operation in the value. Othewise the schema will use the type as the name of the method to be called.

See `Mongolid\Schema\Schema::objectId` method for example. It means that if a field type (in `$fields`) is defined as `"objectId"`, it will pass trought the `Mongolid\Schema\Schema::objectId` before being saved in the database.

Because of this you can create your own custom field types easily. Just create a new public method in your schema and you are ready to use it's name as a type definition in `$fields`.

The last option is to define a field as another schema by using the syntax _'schema.&lt;Class&gt;'_ This represents one or more embedded documents that will be formated using another _Schema_ class.

### Default Schema $field types

By default the `Mongolid\Schema\Schema` contains the following types:

| type               | description                                                                                                        |
|--------------------|--------------------------------------------------------------------------------------------------------------------|
| &lt;scalar type&gt;      | Casts field to `int`, `integer`, `bool`, `boolean`, `float`, `double`, `real` or `string`.                         |
| objectId           | If the field is not defined or if it's a string compatible with ObjectId notation it will be saved as an ObjectId. |
| sequence           | If value is zero or not defined a new auto-increment integer will be "generated" for that collection.              |
| createdAtTimestamp | Prepares the field to be the datetime that the document has been created. (MongoDB\BSON\UTCDateTime)               |
| updatedAtTimestamp | Prepares the field to be now whenever the document is saved. (MongoDB\BSON\UTCDateTime)                            |
| schema.&lt;Class&gt;     | Delegates the objects or arrays within the field to be mapped by another schema.                                   |

But you can easily create your own types. For example:

```php
class MySchema extends Mongolid\Schema\Schema
{
    ...

    $fields = [
        '_id' => 'sequence',
        'name' => 'uppercaseString' // Will be processed by the method below
    ];

    public function uppercaseString(string $value) {
        return strtoupper($value);
    }
}
```

### Schema definition for embeded documents

By using the `"schema.<Class>"` syntax you can create schemas and map Entities for embeded documents. For example, with the definition below:

```php
class PostSchema extends Mongolid\Schema\Schema
{
    public $entityClass = 'Post';
    public $collection = 'posts';

    $fields = [
        '_id'      => 'objectId',
        'title'    => 'string',
        'body'     => 'string',
        'comments' => 'schema.CommentSchema' // Embeds comments
    ];
}

class CommentSchema extends Mongolid\Schema\Schema
{
    public $entityClass = 'Comment';
    public $collection = null; // Optional since all comments will be embedded

    $fields = [
        '_id'    => 'objectId',
        'body'   => 'string',
        'author' => 'string'
    ];
}
```

The MongoDB document

```javascript
{
    _id: ObjectId("5099803df3f4948bd2f98391"),
    title: "Foo bar",
    body: "Lorem ipsum",
    comments: [
        {
            _id: ObjectId("507f1f77bcf86cd799439011"),
            body: "Awesome!",
            author: "John Doe",
        },
        {
            _id: ObjectId("507f191e810c19729de860ea"),
            body: "Cool!",
            author: "Alan Turing",
        }
    ]
}
```

...Will be mapped to the actual domain Entities:

```php
$post = $manager->getMapper(Post::class)->first('5099803df3f4948bd2f98391');

get_class($post) // Post

get_class($post->comments[1]) // Comment;

$post->comments[1]->author // Alan Turing

// And if you are using Mongolid\Model\Relations trait in your entity ;)
$post->comments()->sort(['author' => 1])->first()->name // Alan Turing
```

## Entity helpers

Mongolid provides some helpers for Domain Entities in the form of traits.

### Attribute trait

The `Mongolid\Model\Attributes` trait adds attribute getters, setters and the `fill` method that can be used with `$fillable` and `$guarded` properties to make sure that only the correct attributes will be set.

By including this trait all the entity attributes will be isolated in the `$attributes` property of your entity and [mass assignment capabilities](./basics.md#mass-assignment) will be available.

See `Mongolid\Model\Attributes` for more information.

### Relations trait

The `Mongolid\Model\Relations` trait adds functionality for handling relations between entities. It will enable `embedsOne`, `embedsMany`, `referencesOne`,`referencesMany` methods and all the [relationship capabilities](./relationships.md) in the entity.

**Using relationship methods in DataMapper pattern**

When using relationship methods (`embedsMany` for example) in DataMapper pattern, instead of referencing the _Entity_ class you should reference the _Schema_ class, for example:

```php
    ...
    public function comments()
    {
        return $this->embedsMany('CommentSchema', 'comments');
    }
```

In the example above Mongolid will use the `CommentSchema` to determine which Entity object should be used to map data of the `comments` field.

See `Mongolid\Model\Relations` for more information.
