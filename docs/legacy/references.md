## References Relationships

Of course, your database collections are probably related to one another. For example, a blog post may have many comments, or an order could be related to the user who placed it. Mongolid makes managing and working with these relationships easy. MongoDB and Mongolid in short supports four types of relationships:

- [References One](#references-one)
- [References Many](#references-many)

> **Note:** MongoDB **relationships doesn't works like in a Relational database**. In MongoDB, data modeling decisions involve determining how to structure the documents to model the data effectively. The primary decision is whether to embed or to use references. See [MongoDB - Data Modeling Decisions](https://docs.mongodb.org/manual/core/data-model-design/) for more information on this subject.

---

### References One

In Mongolid a reference is made by storing the `_id` of the referenced object. 

Referencing provides more flexibility than embedding; however, to resolve the references, client-side applications must issue follow-up queries. In other words, using references requires more roundtrips to the server.

In general, use references when embedding would result in duplication of data and would not provide sufficient read performance advantages to outweigh the implications of the duplication. Read [MongoDB - Relationships with Document References](https://docs.mongodb.org/manual/tutorial/model-referenced-one-to-many-relationships-between-documents/) to learn more how to take advantage of referencing in MongoDB.

> **Note:** MongoDB **relationships doesn't works like in a Relational database**. In MongoDB, data modeling decisions involve determining how to structure the documents to model the data effectively. If you try to create references between documents like you would do in a relational database you will end up with _"n+1 problem"_ and poor performance.

**Defining A References One Relation**

```php
class Post extends \Mongolid\Model\AbstractModel {
    protected $collection = 'posts';

    public function author()
    {
        return $this->referencesOne(User::class, 'author');
    }

}

class User extends \Mongolid\Model\AbstractModel {
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
// The object that will be embedded
$user = new User();
$user->name = 'John';
$user->save() // This will populates the $user->_id

// The object that will contain the user
$post = Post::first('4af9f23d8ead0e1d32000000');

// This method will attach the $phone _id into the phone attribute of the user
$post->attach('author', $user);

// This is an alias to the method called above.
$post->attachToAuthor($user);

// This will will also work
$post->author = $user->_id;

$post->save();

$post->author(); // Will return a User object
```

> **Note:** When using Mongolid models you will need to call the `save()` method after embedding or attaching objects. The changes will only persists after you call the 'save()' method.

### References Many

In Mongolid a _References Many_ is made by storing the `_id`s of the referenced objects.

Referencing provides more flexibility than embedding; however, to resolve the references, client-side applications must issue follow-up queries. In other words, using references requires more roundtrips to the server.

In general, use references when embedding would result in duplication of data and would not provide sufficient read performance advantages to outweigh the implications of the duplication. Read [MongoDB - Relationships with Document References](https://docs.mongodb.org/manual/tutorial/model-referenced-one-to-many-relationships-between-documents/) to learn more how to take advantage of referencing in MongoDB.

**Defining A References Many Relation**

```php
class User extends \Mongolid\Model\AbstractModel {
    protected $collection = 'users';

    public function questions()
    {
        return $this->referencesMany(Question::class, 'questions');
    }

}

class Question extends \Mongolid\Model\AbstractModel {
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
- Return the [`Mongolid\Cursor\Cursor`](../cursor) with the related posts

In order to set a reference to a document use the attach method or it's alias. For example:

```php
$postA = new Post();
$postA->title = 'Nice post';

$postB = new Post();
$postB->title = 'Nicer post';

$user = User::first('4af9f23d8ead0e1d32000000');

// Both ways work
$user->attachToPosts($postA);
$user->attach('posts', $postB);

$user->save();
```

> **Note:** When using Mongolid models you will need to call the `save()` method after embedding or attaching objects. The changes will only persists after you call the 'save()' method.

You can use `detach` method with the referenced object or it's `_id` in order to remove a single reference.
