# Usage

The Query Builder is built as a fluent API, fluent means that you can chain
almost every method call, exception being the methods that create new
objects that requires you to manipulate.

## Example query

Let's write the following SQL query:

```sql
WITH "variation" AS (
    SELECT "product_id", array_agg("name") as "allowed"
    FROM "product_variation"
    GROUP BY
        "product_id"
)
SELECT
    "product"."price",
    "user_backet"."quantity",
    "variation"."allowed" as "allowed_variations"
FROM "product"
INNER JOIN "user_basket"
    ON "user_basket"."product_id" = "product"."id"
LEFT JOIN "variation"
    ON "variation"."product_id" = "product"."id"
WHERE
    "user_backet"."user_id" = 12
    AND some_unsupported_function()
ORDER BY
    "user_backed"."added_at" ASC
;
```

You may do it in many ways, let's see one of them:

```php
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Where;

$select = $queryBuilder->select('product');

$select
    ->createWith('variation', 'product_variation')
    ->column('product_id')
    ->columnRaw('array_agg("name")', 'allowed')
    ->groupBy('product_id')
;

$select
    ->column('product.price')
    ->column('user_backet.quantity')
    ->column('variation.allowed')
    ->join(
        'product',
        '"user_basket"."product_id" = "product"."id"'
    )
    ->leftJoin(
        'variation'
        fn (Where $where) => $where->isEqual('variation.product_id', new ColumnName('product.id'))
    )
    ->where('user_backet.user_id', 12)
    ->whereRaw('some_unsupported_function()')
    ->orderBy('user_backed"."added_at')
;
```

As you can see, there are many details revealed by this code.

## Selecting columns

## Raw expression are allowed (almost) everywhere

For each specialized method such as `Select::column()`, which aims to parse
the user input like the `MakinaCorpus\QueryBuilder\Expression\ColumnName`
expression class, exists a `*Raw()` alternative when it makes sense, here
the `Select::columnRaw()` method.

Raw methods accept:
  - any string and will be treated as SQL code,
  - may contain the `?` placeholder, that refers to an argument value,
  - accept an array of arguments as second parameter (number of placeholder
    in the SQL code and value count must match),
  - argument values can always be an `MakinaCorpus\QueryBuilder\Expression`
    instance as well, and will be handled gracefully by the writer,
  - or first parameter itself can be any `MakinaCorpus\QueryBuilder\Expression`
    instance instead of SQL code, case in which arguments are not allowed.


## With statement are Select queries
