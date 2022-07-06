## Embeds Relationships

Read [MongoDB - Embedded Data Models](https://docs.mongodb.org/manual/core/data-model-design/#embedded-data-models) to learn more how to take advantage of document embedding.

- [Embeds One](#embeds-one)
- [Embeds Many](#embeds-many)

---

### Embeds One

An Embeds One relationship is a very basic relation. For example, a `User` model might have one `Phone`. 
We can define this relation in Mongolid:

#### Defining an embeds one relation

```php
class Person extends \Mongolid\Model\AbstractModel {
    // This model is saved in the collection people
    protected $collection = 'people';

    // Method that will be used to access the phone
    public function phone()
    {
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

The first argument passed to the `embedsOne` method is the name of the related model. The second argument is in what attribute that object will be embedded. Once the relationship is defined, we may retrieve it using:

```php
$phone = User::find('4af9f23d8ead0e1d32000000')->phone();
```

Which will translate to:

- Query for the user with the `_id` _'4af9f23d8ead0e1d32000000'_
- Instantiate a **Phone** object with the attributes found in _'phone'_ attribute of the user
- Return that object

In order to embed a document to be used in a Embeds One relationship, simply do the following:

```php
// The object that will be embedded
$phone = new Phone();
$phone->regionCode = '55';
$phone->number = '1532323232';

// The object that will contain the phone
$user = User::first('4af9f23d8ead0e1d32000000');

// This method will embed the $phone into the phone attribute of the user
$user->embed('phone', $phone);

// This is an alias to the method called above.
$user->embedToPhone($phone);

// Not recomended, but also works
$user->phone = $phone->attributes();

// Or (not recomended)
$user->phone = $phone->toArray();

// Or even (not recomended)
$user->phone = [
    'regionCode' => $phone->regionCode,
    'number' => $phone->number
];

$user->save();

// Now we can retrieve the object by calling
$user->phone(); // Will return a Phone object similar to $phone
```

> **Note:** When using Mongolid models you will need to call the `save()` method after embeding or attaching objects. The changes will only persists after you call the 'save()' method.

It's recommended that you don't embed your models by setting the attribute directly. The `embed` method will include an `_id` to identify your embedded document and allow the usage of `unembed` and `embed` to update models.

```php
$user->embed('phone', $phone); // Now, $phone have an _id

$phone->regionCode = 77; // Update the region code

$user->embed($phone); // Will update
```

### Embeds many

An example of a Embeds Many relation is a blog post that "has many" comments. We can model this relation like so:

```php
class Post extends \Mongolid\Model\AbstractModel {
    protected $collection = 'posts';

    public function comments()
    {
        return $this->embedsMany(Comment::class, 'comments');
    }

}

class Comment extends \Mongolid\Model\AbstractModel {
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
$commentA = new Comment();
$commentA->content = 'Cool feature bro!';

$commentB = new Comment();
$commentB->content = 'Awesome!';

$post = Post::first('4af9f23d8ead0e1d32000000');

// Both ways work
$post->embedToComments($commentA);
$post->embed('Comments', $commentB);

$post->save();
```

> **Note:** When using Mongolid models you will need to call the `save()` method after embeding or attaching objects. The changes will only persists after you call the 'save()' method.

The `embed` method will include an `_id` to identify your embedded document and allow the usage of `embed` and `unembed` to update or delete embedded documents:

```php
$commentB->content = "Pretty awesome!";

$post->unembed($commentA); // Removes 'Cool feature bro!'
$post->embed($commentB);   // Updates 'Awesome' to 'Pretty awesome'
```
