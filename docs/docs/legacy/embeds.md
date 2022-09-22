# Embeds Relationships

Read [MongoDB - Embedded Data Models](https://docs.mongodb.org/manual/core/data-model-design/#embedded-data-models) 
to learn more how to take advantage of document embedding.

---

## Embeds One

An Embeds One relationship is a very basic relation. For example, a `User` model might have one `Phone`. 
We can define this relation in Mongolid:

```php title="Defining an embeds one relation"
    class Person extends \Mongolid\Model\AbstractModel {
        // This model is saved in the collection people
        protected $collection = 'people';
    
        // Method that will be used to access the phone
        public function phone()
        {
            // highlight-next-line
            return $this->embedsOne(Phone::class, 'phone');
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
The second argument is in what attribute that object will be embedded. 
Once the relationship is defined, we may retrieve it using:

```php title="Retrieving relation"
    $phone = User::find('4af9f23d8ead0e1d32000000')->phone();
```

:::info
Which will translate to:

- Query for the user with the `_id` _'4af9f23d8ead0e1d32000000'_
- Instantiate a **Phone** object with the attributes found in _'phone'_ attribute of the user
- Return that object
:::

In order to embed a document to be used in a Embeds One relationship, simply do the following:

```php title="Embedding a document"
    // The object that will be embedded
    $phone = new Phone();
    $phone->regionCode = '55';
    $phone->number = '1532323232';
    
    // The object that will contain the phone
    $user = User::first('4af9f23d8ead0e1d32000000');
    
    // This method will embed the $phone into the phone attribute of the user
    // highlight-next-line
    $user->embed('phone', $phone);
    
    // This is an alias to the method called above.
    // highlight-next-line
    $user->embedToPhone($phone);
    
    $user->save();
    
    // Now we can retrieve the object by calling
    $user->phone(); // Will return a Phone object similar to $phone
```

:::info
When using Mongolid models you will need to call the `save()` method after embedding or attaching objects. 
The changes will only persist after you call the 'save()' method.
:::

:::caution
It's recommended that you don't embed your models by setting the attribute directly. 
The `embed` method will include an `_id` to identify your embedded document and allow the usage of `unembed` and `embed` to update models.
```php title="Not recommended ways to embed"
    // Not recomended, but also works
    $user->phone = $phone->attributes();
    
    // Or (not recomended)
    $user->phone = $phone->toArray();
    
    // Or even (not recomended)
    $user->phone = [
        'regionCode' => $phone->regionCode,
        'number' => $phone->number
    ];
```
:::

```php title="Updating embedded document"
    $user->embed('phone', $phone); // Now, $phone have an _id
    
    $phone->regionCode = 77; // Update the region code
    
    $user->embed($phone); // Will update
```

## Embeds many

An example of an Embeds Many relation is a blog post that "has many" comments. We can model this relation like so:

```php title="Defining an embeds many relation"
    class Post extends \Mongolid\Model\AbstractModel {
        protected $collection = 'posts';
    
        public function comments()
        {
            // highlight-next-line
            return $this->embedsMany(Comment::class, 'comments');
        }
    
    }
    
    class Comment extends \Mongolid\Model\AbstractModel {
        // This model will be embedded only
        protected $collection = null;
    }
```

Now we can access the post's comments `EmbeddedCursor` through the comments method:

```php title="Retrieving relation"
    $comments = Post::find('4af9f23d8ead0e1d32000000')->comments();
```

Now you can iterate and perform cursor operations in the `EmbeddedCursor` that is retrieved

```php title="Cursor iterating"
    foreach($comments->limit(10) as $comment)
    {
        // do something
    }
```

In order to embed a document to be used in an Embeds Many relationship, you should use the `embed` method or the alias `embedTo<Attribute>`:

```php title="Embedding a document"
    $commentA = new Comment();
    $commentA->content = 'Cool feature bro!';
    
    $commentB = new Comment();
    $commentB->content = 'Awesome!';
    
    $post = Post::first('4af9f23d8ead0e1d32000000');
    
    // Both ways work
    // highlight-start
    $post->embedToComments($commentA);
    $post->embed('Comments', $commentB);
    // highlight-end
    
    $post->save();
```

:::info
When using Mongolid models you will need to call the `save()` method after embeding or attaching objects. 
The changes will only persist after you call the 'save()' method.
:::

:::info
The `embed` method will include an `_id` to identify your embedded document and allow the usage of `embed` and `unembed` to update or delete embedded documents.
:::

```php title="Example"
    $commentB->content = "Pretty awesome!";
    
    $post->unembed($commentA); // Removes 'Cool feature bro!'
    $post->embed($commentB);   // Updates 'Awesome' to 'Pretty awesome'
```
