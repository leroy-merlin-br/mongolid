---
sidebar_position: 3
---

# Mongolid Cursor

In MongoDB, a cursor is used to iterate through the results of a database query. 
For example, to query the database and see all results:

```php title="Query database"
    $cursor = User::where(['kind' => 'visitor']);
```

In the above example, the `$cursor` variable will be a `Mongolid\Cursor\Cursor`.

The Mongolid's `Cursor` wraps the original `MongoDB\Driver\Cursor` object of the new MongoDB Driver 
in a way that you can build queries in a more fluent and easy way. 
Also, the Mongolid's `Cursor` will make sure to return the instances of your model instead of stdClass or arrays.

:::info
The [Cursor class of the new driver](http://php.net/manual/en/class.mongodb-driver-cursor.php) is not as user-friendly as the old one. 
Mongolid's cursor also make it as easy to use as the old one.
:::

The `Mongolid\Cursor\Cursor` object has a lot of methods that helps you to iterate, refine and get information. 
For example:

```php title="Using cursor"
    $cursor = User::where(['kind'=>'visitor']);
    
    // Sorts the results by given fields. In the example bellow, it sorts by username DESC
    $cursor->sort(['username'=>-1]);
    
    // Limits the number of results returned.
    $cursor->limit(10);
    
    // Skips a number of results. Good for pagination
    $cursor->skip(20);
    
    // Checks if the cursor is reading a valid result.
    $cursor->valid();
    
    // Returns the first result
    $cursor->first();
```

You can also chain some methods:

```php title="Chaining methods"
    $page = 2;
    
    // In order to display 10 results per page
    $cursor = User::all()->sort(['_id'=>1])->skip(10 * $page)->limit(10);
    
    // Then iterate through it
    foreach($cursor as $user) {
        // do something
    }
```
