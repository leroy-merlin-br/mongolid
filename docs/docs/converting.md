---
sidebar_position: 5
---

# Converting models

## Converting to arrays

When building JSON APIs, you may often need to convert your models to arrays. So, Mongolid includes methods for doing so.
To convert a model to an array, you may use the `toArray` method. It returns the document attributes of the model (attributes set to `null` are left out):

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


:::info **Converting a model to json**
Mongolid has no `toJson` method. Use `json_encode($model->toArray())`. See [LegacyRecord](legacy/record.md) for the legacy `toArray`.
:::
