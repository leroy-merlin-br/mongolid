---
sidebar_position: 6
---

# Converting models

## Converting to arrays

When building JSON APIs, you may often need to convert your models to arrays. So, Mongolid includes methods for doing so.
To convert a model and its loaded relationship to an array, you may use the `toArray` method:

```php title="Converting a model to an array"
    $user = User::first();
    
    return $user->toArray();
```

:::tip
Note that [cursors](cursor.md) can be converted to array too:

```php
    return User::all()->toArray();
```
:::

:::caution **Converting a model to json**
This resource is still present only on `LegacyRecord`.
You can see here [LegacyRecord](legacy/record.md) *For compatibility with version 2.x*
:::
