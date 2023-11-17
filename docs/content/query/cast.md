# Typing and casting

This API allows you to propagate your values SQL target type. Type information
is not relevant to the SQL generated code, but will be propagated in the
`MakinaCorpus\QueryBuilder\ArgumentBag` instance, which allows the brige to
use it for value conversion.

## Typing values in raw SQL

When you need to add type information to a value that happens to be
injected into a raw SQL string, use the PostgreSQL type cast syntax
as such:

```php
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($writer instanceof Writer);

$writer->prepare("select ?::int" [1]);
$writer->prepare("select ?::date" [new \DateTimeImmutable()]);
```

For more information please refer to the [placeholder type hint documentation](./placeholder).

## Typing values in query builder

When using the query builder, you are not responsible for writing the SQL
code, but you can hint the SQL writer as such:

```php
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($queryBuilder instanceof QueryBuilder);

$queryBuilder
    ->select()
    ->columnRaw(
        new Value(1, 'int')
    )
;
```

Which will be converted as:

```sql
   select ?
```

## SQL cast

If you are trying to let the SQL server do the cast by itself, you should write
it using the SQL-92 standard ``CAST()`` function as such:

```php
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($writer instanceof Writer);

$writer->prepare(
    <<<SQL
    select cast(? as int)
    SQL,
    [
        1,
    ]
);
```

Which is semantically equivalent to:

```php
use MakinaCorpus\QueryBuilder\Expression\Cast;
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($writer instanceof Writer);

$writer->prepare(
    <<<SQL
    select ?
    SQL,
    [
        new Cast(1, 'int')
    ]
);
```

Or:

```php
use MakinaCorpus\QueryBuilder\Expression\Cast;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($writer instanceof Writer);

$writer->prepare(
    <<<SQL
    select ?
    SQL,
    [
        new Cast(
            new Value(1, 'int')
        )
    ]
);
```

Will all restitute the following SQL code:

```sql
select cast(? as int)
```

