---
sidebar_position: 9
---

# Eager loading

Eager loading avoids the N+1 problem. When you list models and then access a referenced model on each one, Mongolid would run one query per model. With eager loading, Mongolid loads all related models at once and keeps them in the cache.

Eager loading has two parts:

1. The public `$with` property on the model, which describes the related models to load.
2. The `$useCache` parameter of `where`, which turns the feature on for a query.

## Configuring `$with`

Define which related models should be loaded, indexed by a name of your choice:

```php title="Product.php"
class Product extends \Mongolid\LegacyRecord
{
    protected $collection = 'products';

    public $with = [
        'price' => [
            'key' => '_id',
            'model' => Price::class,
        ],
        'shop' => [
            'key' => 'skus.shop_id',
            'model' => Shop::class,
        ],
    ];

    public function price()
    {
        return $this->referencesOne(Price::class, '_id');
    }

    public function skus()
    {
        return $this->embedsMany(Sku::class, 'skus');
    }
}
```

Each entry accepts:

| Option | Description |
|---|---|
| `model` | Class of the related model. |
| `key` | Attribute that holds the referenced `_id`. Defaults to `_id`. |

Use dot notation in `key` to read ids from embedded documents. In the example above, `skus.shop_id` loads the shop of every embedded `Sku`.

## Using `$useCache`

`where` receives `$useCache` as its third argument. It is `false` by default, so no eager loading happens unless you ask for it.

```php
    $products = Product::where([], [], true);

    foreach ($products as $product) {
        $product->price(); // Read from the cache. No extra query.

        foreach ($product->skus() as $sku) {
            $sku->shop(); // Read from the cache. No extra query.
        }
    }
```

When you start iterating the cursor, Mongolid:

1. Runs the query and caches its results.
2. Reads the ids defined in `$with` from the returned documents.
3. Runs one query per `$with` entry (`_id` `$in` the collected ids) and stores each related model in the cache.

Later calls to `referencesOne` find the model in the cache instead of querying the database.

On `LegacyRecord`, `first` and `firstOrFail` also accept `$useCache` as the third argument.

## Limits and cache

- Only the first 100 models of the query are cached, and only the first 100 related models of each `$with` entry. Models after that limit are fetched normally.
- Related models are stored in the registered `Mongolid\Util\CacheComponentInterface` under the key `{collection}:{_id}` (for example `prices:5f...`).
- Entries are stored for 36 minutes.
- If `$with` is empty or the query returns no documents, nothing is cached.
- Dot notation in `key` supports one level only (`embedded_field.attribute`).

## Errors

If the `key` of an entry is not found on the documents, Mongolid throws `Mongolid\Query\EagerLoader\Exception\EagerLoaderException` with the message `Referenced key was not found on child model.`

:::info
Eager loading runs through the `SchemaCacheableCursor`, which is the cursor used by `LegacyRecord` and the `DataMapper`. The `CacheableCursor` returned by `AbstractModel::where` also caches query results, but it does not eager load the `$with` models.
:::
