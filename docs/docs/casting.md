---
sidebar_position: 6
---

# Casting attributes

## Casting to DateTime


With Mongolid, you can define attributes to be cast to `DateTime` or `DateTimeImmutable` using `$casts` property in your models. 

```php
class Person extends \Mongolid\Model\AbstractModel {
    protected $casts = [
        'expires_at' => 'datetime',
        'birthdate' => 'immutable_datetime',        
    ];
}
```

When you define an attribute to be cast as `DateTime` or `DateTimeImmutable`, Mongolid will load it from database will do its trick to return an `DateTime` instance(or `DateTimeImmutable`)  anytime you try to access it with property accessor operator (`->`).

If you need to manipulate its original value on MongoDB, then you can access it through `getDocumentAttributes()` method

To write a value on an attribute with `DateTime` cast, you can use both an `\MongoDB\BSON\UTCDateTime`, `\DateTime` or `\DateTimeImmutable` instance.
Internally, Mongolid will manage to set the property as an UTCDateTime, because it is the datetime format accepted by MongoDB.

Check out some usages and examples:

```php

$user = Person::first();
$user->birthdate; // Returns birthdate as a DateTimeImmutable instance
$user->expires_at; // Returns expires_at as DateTime instance

$user->getOriginalDocumentAttributes()['birthdate']; // Returns birthdate as an \MongoDB\BSON\UTCDateTime instance

// To set a new birthdate, you can pass both UTCDateTime or native's PHP DateTime
$user->birthdate = new \MongoDB\BSON\UTCDateTime($anyDateTime);
$user->birthdate = DateTime::createFromFormat('d/m/Y', '01/03/1970');


```

