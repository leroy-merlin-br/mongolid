---
sidebar_position: 7
---

# Soft delete

Add `Mongolid\Model\SoftDeleteTrait` to a model to keep deleted documents in the collection.

```php title="Product.php"
class Product extends \Mongolid\Model\AbstractModel
{
    use \Mongolid\Model\SoftDeleteTrait;

    protected $collection = 'products';
}
```

## Deleting and restoring

`delete()` does not remove the document. It sets the `deleted_at` attribute to the current date.

```php
    $product->delete();
    $product->isTrashed(); // true

    $product->restore(); // true. Returns false if the model is not trashed
    $product->forceDelete(); // removes the document from the collection
```

To use another attribute name, define a `DELETED_AT` constant on the model:

```php
    const DELETED_AT = 'removed_at';
```

## Querying

`where`, `all` and `first` skip trashed documents (the ones with the `deleted_at` attribute set). `firstOrFail`, `firstOrNew` and `fresh` use `first`, so they skip trashed documents too. Use `withTrashed` to include them. It accepts the same arguments as `where`:

```php
    $products = Product::withTrashed(['category' => 'tools']);
```

:::info
Set the public property `$isSoftDeleteEnabled` to `false` on a model to make `delete()` remove the document permanently.
:::
