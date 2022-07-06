## Legacy Record

With `LegacyRecord` you can do pretty most the same actions that you can do with `AbstractModel`.
Here you find the differences that `AbstractModel` can't do anymore, but `LegacyRecord` still can do for you. 

#### Converting A Model To JSON

```php
return User::find(1)->toJson();
```
