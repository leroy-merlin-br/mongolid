---
sidebar_position: 12
---

# Accessors and mutators

Accessors change the value of an attribute when you read it. Mutators change the value when you set it.
They are disabled by default. Set the `$mutable` property to `true` on the model to enable them.

```php title="Post.php"
class Post extends \Mongolid\Model\AbstractModel
{
    protected $collection = 'posts';

    protected $mutable = true;

    public function getShortNameDocumentAttribute(): string
    {
        return strtoupper($this->getDocumentAttributes()['short_name'] ?? '');
    }

    public function setShortNameDocumentAttribute($value): string
    {
        return trim($value);
    }
}
```

The method name is `get` or `set`, then the attribute name in StudlyCase, then `DocumentAttribute`. For the attribute `short_name`, the methods are `getShortNameDocumentAttribute` and `setShortNameDocumentAttribute`.

- A mutator receives the value being set and returns the value that Mongolid stores.
- An accessor returns the value you get when you read the attribute. The stored value is not changed.
- You only need to define the methods you use.

:::info
On `LegacyRecord`, `$mutable` is public and the methods are named `get<Attribute>Attribute` and `set<Attribute>Attribute`, with the first letter of the attribute name in uppercase. For example, `getTitleAttribute` is the accessor of `title`. See [Legacy Record](legacy/record.md).
:::
