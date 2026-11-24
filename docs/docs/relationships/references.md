---
sidebar_position: 3
---

# References

In Mongolid a reference is made by storing the `_id` of the referenced object.

Referencing provides more flexibility than embedding; 
however, to resolve the references, client-side applications must issue follow-up queries. 
In other words, using references requires more roundtrips to the server.

In general, use references when embedding would result in duplication of data and would not provide sufficient 
read performance advantages to outweigh the implications of the duplication. 
Read [MongoDB - Relationships with Document References](https://docs.mongodb.org/manual/tutorial/model-referenced-one-to-many-relationships-between-documents/) 
to learn more how to take advantage of referencing in MongoDB.

:::info
MongoDB **relationships doesn't work like in a Relational database**. 
In MongoDB, data modeling decisions involve determining how to structure the documents to model the data effectively.
:::
:::caution
If you try to create references between documents like you would do in a relational database you will end up with _"n+1 problem"_ and poor performance.
:::



[Legacy References](../legacy/references.md) *For compatibility with version 2.x*

---

## References One

### Defining A References One Relation

```php
    class Post extends \Mongolid\Model\AbstractModel {
        protected $collection = 'posts';
    
        public function author()
        {
            // highlight-next-line
            return $this->referencesOne(User::class, 'author_id');
        }
    
    }
    
    class User extends \Mongolid\Model\AbstractModel {
        protected $collection = 'users';
    }
```

The first argument passed to the `referencesOne` method is the name of the related model, 
the second argument is the attribute where the referenced model `_id` will be stored. 
The attribute **cannot** have the same name as the relation method. Mongolid throws `Mongolid\Model\Exception\InvalidFieldNameException` in that case.
The optional third argument is the key of the referenced model (default `_id`).
If you omit the second argument, Mongolid uses the snake case relation name + `_` + the key without the leading underscore (`author_id` in this example, `author_code` for key `code`).
Once the relationship is defined, we may retrieve it using the following method:

```php
    $user = Post::first('4af9f23d8ead0e1d32000000')->author()->get();

    // or, reading the relation as a property
    $user = Post::first('4af9f23d8ead0e1d32000000')->author;
```

:::info Explanation:
- Query for the post with the `_id` _'4af9f23d8ead0e1d32000000'_
- Query for the user with the `_id` equals to the _author_id_ attribute of the post that returns relationship object
- Get method to return the User model filled, or `null` when the attribute is not set
:::

In order to set a reference to a document:
```php
    // The object that will be referenced
    $user = new User();
    $user->name = 'John';
    $user->save() // This will populate the $user->_id
    
    // The object that will contain the user
    $post = Post::first('4af9f23d8ead0e1d32000000');
    
    // This method will attach the $user _id into the author_id attribute of the post
    // highlight-next-line
    $post->author()->attach($user);
    
    $post->save();
    
    $post->author()->get(); // Will return a User object
```

To remove the reference, call `$post->author()->detach()` and then `save()`.

:::info
When using Mongolid models you will need to call the `save()` method after embedding or attaching objects. 
The changes will only persist after you call the 'save()' method.
:::

## References Many

In Mongolid a _References Many_ is made by storing the `_id`s of the referenced objects.

### Defining A References Many Relation

```php
    class User extends \Mongolid\Model\AbstractModel {
        protected $collection = 'users';
    
        public function questions()
        {
            // highlight-next-line
            return $this->referencesMany(Question::class, 'question_ids');
        }
    
    }
    
    class Question extends \Mongolid\Model\AbstractModel {
        protected $collection = 'questions';
    }
```

The first argument passed to the `referencesMany` method is the name of the related model, 
the second argument is the attribute where the `_id`s will be stored. 
The same naming rules of `referencesOne` apply. When the second argument is omitted, the attribute is the snake case relation name + `_` + the pluralized key (`questions_ids` in this example).
Once the relationship is defined, we may retrieve it using the following method:

```php
    $questions = User::first('4af9f23d8ead0e1d32000000')->questions()->get();
```

:::info Explanation:
- Query for the user with the `_id` _'4af9f23d8ead0e1d32000000'_
- Query for all the questions with the `_id` in the user's _question_ids_ attribute and return a relationship object
- Method `get()` will return the [`Mongolid\Cursor\Cursor`](../cursor.md) with the related questions
:::

In order to set a reference to a document use the `attach` method. For example:

```php
    $questionA = new Question();
    $questionA->title = 'Nice question';
    
    $questionB = new Question();
    $questionB->title = 'Nicer question';
    
    $user = User::first('4af9f23d8ead0e1d32000000');
    
    // To attach a document
    $user->questions()->attach($questionA);
    
    // To attach many documents
    $user->questions()->attachMany([$questionA, $questionB]);
    
    // To replace the current documents
    $user->questions()->replace([$questionA, $questionB]);
    
    // To detach a single document
    $user->questions()->detach($questionA);
    
    // To detach all documents
    $user->questions()->detachAll();
    
    $user->save();
```

`attach` ignores a document that is already attached, and it generates an `_id` for documents that do not have one.

:::info
When using Mongolid models you will need to call the `save()` method after embedding or attaching objects. 
The changes will only persist after you call the 'save()' method.
:::
