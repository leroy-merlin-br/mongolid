---
sidebar_position: 3
---

# References Relationships

Of course, your database collections are probably related to one another. For example, a blog post may have many comments, or an order could be related to the user who placed it. Mongolid makes managing and working with these relationships easy. MongoDB and Mongolid in short supports four types of relationships:

- [References One](#references-one)
- [References Many](#references-many)

:::note
MongoDB **relationships doesn't work like in a Relational database**. 
In MongoDB, data modeling decisions involve determining how to structure the documents to model the data effectively. 
The primary decision is whether to embed or to use references. 
See [MongoDB - Data Modeling Decisions](https://docs.mongodb.org/manual/core/data-model-design/) for more information on this subject.
:::

---

## References One

In Mongolid a reference is made by storing the `_id` of the referenced object. 

Referencing provides more flexibility than embedding; however, to resolve the references, client-side applications must issue follow-up queries. In other words, using references requires more roundtrips to the server.

In general, use references when embedding would result in duplication of data and would not provide sufficient read performance advantages to outweigh the implications of the duplication. Read [MongoDB - Relationships with Document References](https://docs.mongodb.org/manual/tutorial/model-referenced-one-to-many-relationships-between-documents/) to learn more how to take advantage of referencing in MongoDB.

:::caution
MongoDB **relationships doesn't work like in a Relational database**. 
In MongoDB, data modeling decisions involve determining how to structure the documents to model the data effectively. 
If you try to create references between documents like you would do in a relational database you will end up with _"n+1 problem"_ and poor performance.
:::

### Defining A References One Relation

```php
    class Post extends \Mongolid\LegacyRecord {
        protected $collection = 'posts';

        public function author()
        {
            // highlight-next-line
            return $this->referencesOne(User::class, 'author');
        }
    }
    
    class User extends \Mongolid\LegacyRecord {
        protected $collection = 'users';
    }
```

The first argument passed to the `referencesOne` method is the name of the related model, 
the second argument is the attribute where the referenced model `_id` will be stored. 
Once the relationship is defined, we may retrieve it using the following method:

```php
    $user = Post::first('4af9f23d8ead0e1d32000000')->author();
```

:::info
This statement will perform the following:

- Query for the post with the `_id` _'4af9f23d8ead0e1d32000000'_
- Query for the user with the `_id` equals to the _author_ attribute of the post
- Return that object
:::

By default, the referenced model is read from the cache when it is available. Pass `false` as the third argument, for example `referencesOne(User::class, 'author', false)`, to always query the database.

In order to set a reference to a document, simply set the attribute used in the relationship to the reference's `_id` 
or use the attachment method, or it's alias. For example:

```php
    // The object that will be referenced
    $user = new User();
    $user->name = 'John';
    $user->save() // This will populate the $user->_id
    
    // The object that will hold the reference
    $post = Post::first('4af9f23d8ead0e1d32000000');
    
    // This method will attach the $user _id into the author attribute of the post
    // highlight-next-line
    $post->attach('author', $user);
    
    // This is an alias to the method called above.
    // highlight-next-line
    $post->attachToAuthor($user);
    
    // This will also work
    $post->author = $user->_id;
    
    $post->save();
    
    $post->author(); // Will return a User object
```

:::info
When using Mongolid models you will need to call the `save()` method after embedding or attaching objects. 
The changes will only persist after you call the 'save()' method.
:::

## References Many

In Mongolid a _References Many_ is made by storing the `_id`s of the referenced objects.

Referencing provides more flexibility than embedding; however, to resolve the references, client-side applications must issue follow-up queries. In other words, using references requires more roundtrips to the server.

In general, use references when embedding would result in duplication of data and would not provide sufficient read performance advantages to outweigh the implications of the duplication. Read [MongoDB - Relationships with Document References](https://docs.mongodb.org/manual/tutorial/model-referenced-one-to-many-relationships-between-documents/) to learn more how to take advantage of referencing in MongoDB.

### Defining A References Many Relation

```php
    class User extends \Mongolid\LegacyRecord {
        protected $collection = 'users';
    
        public function questions()
        {
            // highlight-next-line
            return $this->referencesMany(Question::class, 'questions');
        }
    
    }
    
    class Question extends \Mongolid\LegacyRecord {
        protected $collection = 'questions';
    }
```

The first argument passed to the `referencesMany` method is the name of the related model, 
the second argument is the attribute where the `_id`s will be stored. 
Once the relationship is defined, we may retrieve it using the following method:

```php
    $questions = User::first('4af9f23d8ead0e1d32000000')->questions();
```

:::info
This statement will perform the following:

- Query for the user with the `_id` _'4af9f23d8ead0e1d32000000'_
- Query for all the questions with the `_id` in the user's _questions_ attribute
- Return a cursor with the related questions
:::

In order to set a reference to a document use the attachment method, or it's alias. For example:

```php
    $questionA = new Question();
    $questionA->title = 'Nice question';
    
    $questionB = new Question();
    $questionB->title = 'Nicer question';
    
    $user = User::first('4af9f23d8ead0e1d32000000');
    
    // Both ways work
    // highlight-start
    $user->attachToQuestions($questionA);
    $user->attach('questions', $questionB);
    // highlight-end
    
    $user->save();
```

:::info
When using Mongolid models you will need to call the `save()` method after embedding or attaching objects. 
The changes will only persist after you call the 'save()' method.
:::

:::tip
You can use `detach` method with the referenced object, or it's `_id` in order to remove a single reference.
:::
