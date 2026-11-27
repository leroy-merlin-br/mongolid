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



[Legacy References](docs/legacy/references.md) *For compatibility with version 2.x*

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
The second argument is optional. When it is omitted, Mongolid uses the snake case relation name followed by `_` and the key without its leading underscore. For example, `author()` stores the reference in `author_id`. Use a field name that differs from the method name, or Mongolid throws `Mongolid\Model\Exception\InvalidFieldNameException`.
The optional third argument is the attribute of the related model used to find it. It defaults to `_id`. For example, `referencesOne(User::class, 'author_code', 'code')` queries the user by `code`.
Once the relationship is defined, we may retrieve it using the following method:

```php
    $user = Post::first('4af9f23d8ead0e1d32000000')->author()->get();
```

:::info Explanation:
- Query for the post with the `_id` _'4af9f23d8ead0e1d32000000'_
- Query for the user with the `_id` equals to the _author_ attribute of the post that returns relationship object
- Get method to return the Author model filled
:::

In order to set a reference to a document:
```php
    // The object that will be embedded
    $user = new User();
    $user->name = 'John';
    $user->save() // This will populate the $user->_id
    
    // The object that will contain the user
    $post = Post::first('4af9f23d8ead0e1d32000000');
    
    // This method will attach the $phone _id into the phone attribute of the user
    // highlight-next-line
    $post->author()->attach($user);
    
    $post->save();
    
    $post->author()->get(); // Will return a User object

    // To remove the reference
    $post->author()->detach();
```

`get()` returns `null` when the attribute is empty. `attach` generates an `_id` for the attached model when it has none.

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
            return $this->referencesMany(Question::class, 'questions_ids');
        }
    
    }
    
    class Question extends \Mongolid\Model\AbstractModel {
        protected $collection = 'questions';
    }
```

The first argument passed to the `referencesMany` method is the name of the related model, 
the second argument is the attribute where the `_id`s will be stored. 
When the second argument is omitted, Mongolid uses the snake case relation name followed by `_` and the plural of the key without its leading underscore. 
For example, `questions()` stores the references in `questions_ids`. 
The optional third argument is the attribute of the related model used to find the documents, and defaults to `_id`. 
For example, `referencesMany(Question::class, 'questions_codes', 'code')` stores the `code` of each attached question in `questions_codes`, and queries the questions where `code` is in that list.
Once the relationship is defined, we may retrieve it using the following method:

```php
    $posts = User::first('4af9f23d8ead0e1d32000000')->posts()->get();
```

:::info Explanation:
- Query for the user with the `_id` _'4af9f23d8ead0e1d32000000'_
- Query for all the posts with the `_id` in the user's _posts_ attribute and return a relationship object
- Method `get()` will return the [`Mongolid\Cursor\Cursor`](docs/cursor.md) with the related posts
:::

In order to set a reference to a document use the `attach` method. For example:

```php
    $postA = new Post();
    $postA->title = 'Nice post';
    
    $postB = new Post();
    $postB->title = 'Nicer post';
    
    $user = User::first('4af9f23d8ead0e1d32000000');
    
    // To attach a document
    $user->posts()->attach($postA);
    
    // To attach many documents
    $user->posts()->attachMany([$postA, $postB]);
    
    // To replace the current documents
    $user->posts()->replace([$postA, $postB]);
    
    // To detach a single document
    $user->posts()->detach($postA);
    
    // To detach all documents
    $user->posts()->detachAll();
    
    $user->save();
```

Attaching a document that is already attached does nothing. `detach` receives the model, not an `_id`.

:::info
When using Mongolid models you will need to call the `save()` method after embedding or attaching objects. 
The changes will only persist after you call the 'save()' method.
:::
