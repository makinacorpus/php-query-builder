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

Which will then generate the following SQL code:

```sql
select ?;
select ?;
```

Type information is erased from final generated SQL code, only kept in the
`MakinaCorpus\QueryBuilder\ArgumentBag` instance that results from the
SQL code generation.

Please note that you may additionnaly propagate the type information using
a `MakinaCorpus\QueryBuilder\Expression\Value` instance instead. The following
code is semantically equivalent to the upper example:

```php
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($writer instanceof Writer);

$writer->prepare("select ?" [new Value(1, 'int')]);
$writer->prepare("select ?" [new Value(new \DateTimeImmutable(), 'date')]);
```

:::warning
Please note that anything you cast which is not ``?`` will be left untouched.
:::

For example:

```php
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($writer instanceof Writer);

$writer->prepare("select 1::int");
```

Will be sent to the server as:

```sql
select 1::int
```

Which is probably not something you want to do when you are not using
PostgreSQL.

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

