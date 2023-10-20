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

If you need to manipulate its original value on MongoDB, then you can access it through `getOriginalDocumentAttributes()` method

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

## Casting to Enum with backing value (aka BackedEnum)

With Mongolid, you can define attributes to be cast to enum using `$casts` property in your models. 

```php
enum Size: string
{
    case Small = 'small';
    case Big = 'big';
}

class Box extends \Mongolid\Model\AbstractModel {
    protected $casts = [
        'box_size' => Size::class,
    ];
}
```

When you define an attribute to be cast as a enum type, Mongolid will load it from database will do its trick to return an enum instance anytime you try to access it with property accessor operator (`->`).

If you need to manipulate its raw value, then you can access it through `getDocumentAttributes()` method.

To write a value on an attribute with enum cast, you should pass the enum instance like `Size::Small`
Internally, Mongolid will manage to set the property as enum backing value.

Check out some usages and examples:

```php
$box = Box::first();
$box->box_size = Size::Small; // Set box_size with enum instance
$box->box_size; // Returns box_size as enum instance
$box->getDocumentAttributes()['box_size']; // Returns box_size backing value
```

## Custom Casting

With Mongolid, you can define a custom cast implement a CastInterface.

```php
class HashCast implements \Mongolid\Model\Casts\CastInterface
{
    public function __construct(
        private string $algo
    ) {
    }

    public function get(mixed $value): ?string
    {
        return $value
    }

    public function set(mixed $value): ?string
    {
        if (is_null($value)) {
            return $value;
        }

        return hash($this->algo, $value);     
    } 
}

class Person extends \Mongolid\Model\AbstractModel {
    protected $casts = [
        'password' => HashCast::class . ':sha256',
    ];
}
```

Check out some usages and examples:
```php
$person = Person::first();
$person->password; // Returns saved hash
$person->password = 123; // Set sha256 hash to 123
$person->password; // Returns saved hash to 123
```
