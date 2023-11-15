# Placeholder type hint

## Placeholder identifier escaping

Raw SQL [placholders](./placeholder) can be used to set user-given values to
prevent injection or complex expressions inside a raw SQL string, but they also
can help you set dynamic identifiers in your queries.

Use the `?::ESCAPER` where `ESCAPER` can be any one of the following table:

| Escaper    | Type of escaping                       | Outputs                               | Removes placeholder |
|------------|----------------------------------------|---------------------------------------|---------------------|
| array      | Hint the value as being an array       | `\QueryBuilder\Expression\ArrayValue` | No                  |
| column     | Escape string a column name            | `\QueryBuilder\Expression\ColumnName` | Yes                 |
| identifier | Escape string an abitrary identifier   | `\QueryBuilder\Expression\Identifier` | Yes                 |
| row        | Hint the value as being a constant row | `\QueryBuilder\Expression\Row`        | No                  |
| table      | Escape string a table name             | `\QueryBuilder\Expression\TableName`  | Yes                 |
| value      | Hint the value as being a vlaue        | `\QueryBuilder\Expression\Value`      | No                  |

For example:

```php
use MakinaCorpus\QueryBuilder\ExpressionFactory;

ExpressionFactory::raw(
    <<<SQL
    select
        ?::column as ?::identifier
    from ?::table
    where
        ?::column = ?::array
    SQL,
    [
        'some_table.some_column',
        'some.alias',
        'some_schema.some_table',
        "other_column",
        [1, 2, 3],
    ],
);
```

will generate the following SQL code:

```sql
    select
        "some_table"."some_column" as "some.alias"
    from "some_schema"."some_table"
    where
        "other_column" = array[#1, #2, #3]
```

::: warning
Typo errors in escaper names will not raise errors, but will be silently passed
as a value type hint instead.
:::

## Placeholder value type hint

### Syntax

When used for values, placeholder allows you to specify the value type which
will lead the converter for converting it to an SQL string.

Use the `?::TYPE` syntax, for example:

```php
use MakinaCorpus\QueryBuilder\ExpressionFactory;

ExpressionFactory::raw(
    <<<SQL
    select ?::bool
    SQL,
    [
        1,
    ],
);
```

will lead to this SQL execution on the server side:

```SQL
select true
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
Please note that anything you cast which is not `?` will be left untouched.
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

### Allowed types

Please refer to the [data type matrix](../query/datatype) for a complete list
of supported type hints and value converters.
