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

When you define an attribute to be cast as `DateTime` or `DateTimeImmutable`, Mongolid returns a `DateTime` (or `DateTimeImmutable`) instance anytime you try to access it with property accessor operator (`->`).

If you need the raw value stored for the attribute, you can access it through the `getDocumentAttributes()` method. 
To get the values as they were when the model was loaded or last persisted, use `getOriginalDocumentAttributes()`.

Only `datetime` and `immutable_datetime` are valid cast names. Any other name throws `Mongolid\Model\Casts\Exceptions\InvalidCastException` when the attribute is accessed or set.

To write a value on an attribute with `DateTime` cast, you can use both an `\MongoDB\BSON\UTCDateTime`, `\DateTime` or `\DateTimeImmutable` instance.
Internally, Mongolid will manage to set the property as an UTCDateTime, because it is the datetime format accepted by MongoDB.

`LegacyRecord` also supports `$casts`. See [Legacy Record](legacy/record.md).

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


## Mutators

Set `$mutable` to `true` on the model to transform attributes when they are read or written. Mongolid looks for a method named `get` or `set`, the studly case name of the attribute and `DocumentAttribute`.

```php
class Person extends \Mongolid\Model\AbstractModel {
    protected $mutable = true;

    // Runs on $person->short_name. It takes no arguments and replaces the stored value.
    public function getShortNameDocumentAttribute(): string
    {
        return strtoupper($this->name);
    }

    // Runs on $person->short_name = 'abc'. The returned value is stored.
    public function setShortNameDocumentAttribute($value): string
    {
        return strtolower($value);
    }
}
```

Without `$mutable`, the methods are ignored. `isset($person->short_name)` also uses the `get` mutator.
