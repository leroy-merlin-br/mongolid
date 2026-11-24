---
sidebar_position: 2
---

# Embeds

Read [MongoDB - Embedded Data Models](https://docs.mongodb.org/manual/core/data-model-design/#embedded-data-models) 
to learn more how to take advantage of document embedding.

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
            // highlight-next-line
            return $this->embedsOne(Phone::class, 'phone_data');
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
The attribute **cannot** have the same name as the relation method. Mongolid throws `Mongolid\Model\Exception\InvalidFieldNameException` in that case.
If you omit the second argument, Mongolid uses `embedded_` + the snake case relation name (`embedded_phone` in this example).
Once the relationship is defined, we can retrieve and embed documents.

### To retrieve an embedded document:

1. First we get the relation:

    ```php
        $relation = Person::first('4af9f23d8ead0e1d32000000')->phone();
    ```

    :::info **Explanation**:

    - Query for the person with the `_id` _'4af9f23d8ead0e1d32000000'_
    - The `phone()` method will return an `EmbedsOne` instance, that is extended of an `AbstractRelation` class.
    - That is, the relationship we were trying to obtain.
    :::

2. Then we can obtain the model this way:

    ```php
        $phone = $relation->get();
    ```
    
    OR inline:

    ```php
        $phone = Person::first('4af9f23d8ead0e1d32000000')->phone()->get();
    ```

    OR by reading the relation as a property, which returns the cached result:

    ```php
        $phone = Person::first('4af9f23d8ead0e1d32000000')->phone;
    ```

    :::info **Explanation**

    - Return the object stored in the `phone_data` attribute of the person
    :::

### In order to embed a document:

```php
    // The object that will be embedded
    $phone = new Phone();
    $phone->regionCode = '55';
    $phone->number = '1532323232';
    
    // The object that will contain the phone
    $person = Person::first('4af9f23d8ead0e1d32000000');
    
    // This method will embed the $phone into the phone_data attribute of the person
    // highlight-next-line
    $person->phone()->add($phone);
    
    $person->save();
    
    // Now we can retrieve the relationship by calling
    $person->phone(); // Will return an EmbedsOne object
```

To remove the embedded document, call `$person->phone()->remove()` and then `save()`.

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

As with `embedsOne`, the attribute cannot have the same name as the relation method. When omitted, it is `embedded_` + the snake case relation name.

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

`add` removes any embedded document with the same `_id` before adding, so adding a document again updates it. Mongolid generates an `_id` for embedded documents that do not have one.

:::info
When using Mongolid models you will need to call the `save()` method after embeding or attaching objects. 
The changes will only persist after you call the 'save()' method.
:::
