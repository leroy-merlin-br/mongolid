# Embeds

Read [MongoDB - Embedded Data Models](https://docs.mongodb.org/manual/core/data-model-design/#embedded-data-models) 
to learn more how to take advantage of document embedding.

:::warning AbstractModel and LegacyRecord work differently
This page documents embeds for `Mongolid\Model\AbstractModel`. It is **not** the same as `Mongolid\LegacyRecord`:

|  | `AbstractModel` (this page) | `LegacyRecord` ([legacy](../legacy/embeds.md)) |
|---|---|---|
| What `phone()` returns | An `EmbedsOne` relation object | The embedded `Phone` model itself |
| Getting the embedded model | `$user->phone()->get()` or `$user->phone` | `$user->phone()` |
| Embedding | `$user->phone()->add($phone)` | `$user->embed('phone', $phone)` or `$user->embedToPhone($phone)` |
| Removing | `$user->phone()->remove()` | `$user->unembed('phone', $phone)` |

Do not mix the two. For example, calling `->get()` on the result of `phone()` only works with `AbstractModel`.
:::

- [Legacy embeds](../legacy/embeds.md) *For compatibility with version 2.x*

---

## Embeds One

An Embeds One relationship is a very basic relation.
For example, a `User` model might have one `Phone`.
We can define this relation in Mongolid:

### Defining An Embeds One Relation

```php
    class Person extends \Mongolid\Model\AbstractModel {
        // This model is saved in the collection people
        protected $collection = 'people';
    
        // Method that will be used to access the phone
        public function phone()
        {
            return $this->embedsOne(Phone::class, 'embedded_phone');
        }
    }
    
    class Phone extends \Mongolid\Model\AbstractModel {
        // This model will be embedded only
        protected $collection = null;
    
        public function getFullPhone()
        {
            return '+' . $this->regionCode . $this->number;
        }
    }
```

The first argument passed to the `embedsOne` method is the name of the related model.
The optional second argument is the attribute where the document will be embedded. When it is omitted, Mongolid uses `embedded_` followed by the snake case name of the relation method. For example, a `phone()` relation without the second argument stores the document in `embedded_phone`.
Once the relationship is defined, we can retrieve and embed documents.

### To retrieve an embedded document:

1. First we get the relation:

    ```php
        $relation = User::first('4af9f23d8ead0e1d32000000')->phone();
    ```

    :::info **Explanation**:

    - Query for the user with the `_id` _'4af9f23d8ead0e1d32000000'_
    - The `phone()` method will return an `EmbedsOne` instance, that is extended of an `AbstractRelation` class.
    - That is, the relationship we were trying to obtain.
    :::

2. Then we can obtain the model this way:

    ```php
        $phone = $relation->get();
    ```
    
    OR inline:

    ```php
        $phone = User::first('4af9f23d8ead0e1d32000000')->phone()->get();
    ```

    :::info **Explanation**

    - Instantiate a **Phone** object with the attributes found in `phone` attribute of the user
    - Return that object
    :::

You can also read the relation as a property. `$user->phone` returns the same model as `$user->phone()->get()`:

```php
    $phone = User::first('4af9f23d8ead0e1d32000000')->phone;
```

:::info **Notes**

- The property keeps the result of its first read and reuses it on later reads. `->phone()->get()` has no such cache and reads the embedded document again on every call. The cached value is refreshed after `add()` and `remove()`, or when you assign the field of the relation.
- The field cannot have the same name as the relation method. Mongolid throws `Mongolid\Model\Exception\InvalidFieldNameException`. The default field, `embedded_phone`, avoids this.
- `$user->phone` throws `Mongolid\Model\Exception\NotARelationException` when the `phone()` method does not return a relation.
:::

### In order to embed a document:

```php
    // The object that will be embedded
    $phone = new Phone();
    $phone->regionCode = '55';
    $phone->number = '1532323232';
    
    // The object that will contain the phone
    $user = User::first('4af9f23d8ead0e1d32000000');
    
    // This method will embed the $phone into the embedded_phone attribute of the user  
    $user->phone()->add($phone);
    
    $user->save();
    
    // Now we can retrieve the relationship by calling
    $user->phone(); // Will return Embeds object

    // To remove the embedded document
    $user->phone()->remove();
```

:::info
When using Mongolid models you will need to call the `save()` method after embeding or attaching objects. 
The changes will only persist after you call the 'save()' method.
:::

---

## Embeds many

An example of an Embeds Many relation is a blog post that "has many" comments. We can model this relation like so:

```php
    class Post extends \Mongolid\Model\AbstractModel {
        protected $collection = 'posts';
    
        public function comments()
        {
            // highlight-next-line
            return $this->embedsMany(Comment::class, 'embedded_comments');
        }
    
    }
    
    class Comment extends \Mongolid\Model\AbstractModel {
        // This model will be embedded only
        protected $collection = null;
    }
```

Now we can access the post's comments `EmbeddedCursor` through the comments method:

```php
    $comments = Post::first('4af9f23d8ead0e1d32000000')->comments()->get();
```

Now you can iterate and perform cursor operations in the `EmbeddedCursor` that is retrieved

```php
    foreach($comments->limit(10) as $comment)
    {
        // do something
    }
```

Once you have the EmbedsMany relationship, you are able to use the methods of this object in order to manipulate this embedded documents.

`add` and `remove` identify a comment by its `_id`. `add` generates an `ObjectId` for a comment that has none, and replaces an embedded comment that has the same `_id`. `remove` leaves the other comments in place. The field is re-indexed after each change.

Like:

```php
->add();
->addMany();
->replace();
->remove();
->removeAll();
->get();
```

```php
    $commentA = new Comment();
    $commentA->content = 'Cool feature bro!';
    
    $commentB = new Comment();
    $commentB->content = 'Awesome!';
    
    $post = Post::first('4af9f23d8ead0e1d32000000');
    
    // Add one comment
    $post->comments()->add($commentA);
    
    // Add multiple comments
    $post->comments()->addMany([$commentA, $commentB]);
    
    // Replace all comments already exists
    $post->comments()->replace([$commentA, $commentB]);
    
    // In order to remove a comment
    $post->comments()->remove($commentB);
    
    // To remove all comments
    $post->comments()->removeAll();
    
    $post->save();
```

:::info
When using Mongolid models you will need to call the `save()` method after embeding or attaching objects. 
The changes will only persist after you call the 'save()' method.
:::
