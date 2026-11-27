---
sidebar_position: 10
---

# Accessors and Mutators

Accessors change the value of an attribute when you read it. Mutators change the value when you set it.
They are disabled by default. Set the `$mutable` property to `true` on the model to enable them.


The method names are `get` or `set`, the attribute name in studly case, and `DocumentAttribute`. For example, `getNameDocumentAttribute` is the getter for `name`, and `setNameDocumentAttribute` is the setter.

```php
    class User extends \Mongolid\Model\AbstractModel
    {
        protected $mutable = true;

        public function setNameDocumentAttribute($value)
        {
            return ucfirst($value);
        }

        public function getFullNameDocumentAttribute()
        {
            return $this->first_name.' '.$this->last_name;
        }
    }
```

The `set` mutator receives the value and the value it returns is stored. The `get` mutator takes no arguments and its return value is what you receive when you read the attribute. In the example, `$user->full_name` returns the combined name even though `full_name` is never stored.

:::info
`LegacyRecord` does not use these names. It uses the `Attribute` suffix (for example `getNameAttribute`) and a public `$mutable`. See [Mutators on Legacy Record](./legacy/record.md#mutators).
:::

Keep these points in mind:

- Mutators run only when `$mutable` is `true`. The default is `false`, and the methods are ignored.
- A `set` mutator runs before any `$casts` for the same attribute. A `get` mutator returns its value directly, so `$casts` are not applied to it.
- Mutators run when you assign an attribute with `$user->name = ...`, with `setDocumentAttribute()`, or through `fill()`.
- Do not read the same attribute with `$this->name` or `getDocumentAttribute('name')` inside its own `get` mutator. That calls the mutator again and recurses.
