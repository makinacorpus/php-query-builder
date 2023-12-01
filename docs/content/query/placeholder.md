# Raw SQL parameters placeholders

When writing raw SQL, you can use the `?` parameter placeholder in to arbitrarily:

 - **send user input, values**, that will be later parametrized when sent to the
   database server using the bridge, thus preventing SQL-injection,
 - **escape properly tables, columns and function names or any other identifier**,
   thus preventing SQL-injection or keyword conflicting identifiers ambiguities,
 - **inject arbitrary expressions instances** the writer can format for you.

## Value placeholder

Independently from the final database driver, all value parameters within arbitrary
SQL must be written using the `?` placeholder in raw SQL:

```php
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($writer instanceof Writer);

$writer->prepare(
    <<<SQL
    SELECT * FROM user WHERE mail = ?
    SQL,
    [
        'john.smith@example.com'
    ]
)
```

Additionnaly in order to type hint values for a later bridge value to SQL
conversion to work gracefully, you can use the following syntax: `?::TYPE`.

```php
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($writer instanceof Writer);

$writer->prepare(
    <<<SQL
    SELECT * FROM user WHERE last_login > ?::timestamp
    SQL,
    [
        new DateTime("today 00:00:01")
    ]
)
```

See [the value converter documentation](../converter/converter) for supported data types.

You can specify any number of parameter placeholders within the query, parameters
array must be ordered:

```php
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($writer instanceof Writer);

$writer->prepare(
    <<<SQL
    SELECT * FROM user WHERE last_login > ?::timestamp AND mail = ?
    SQL,
    [
        new \DateTime("today 00:00:01"),
        'john.smith@example.com'
    ]
);
```

:::warning
When using the `?::TYPE` syntax, avoid accidentally using one of the [identifier escapers](#identifier-escaper).
:::

:::warning
Using the `::TYPE` syntax unpreceeded with the `?` character will be left as-is
within the query. This type cast syntax is PostgreSQL specific and you can use
it in your raw SQL.
:::

## Identifier escaper

Placeholders can be used to set user-given values to prevent injection or complex
expressions inside a raw SQL string, but they also can help you set dynamic
identifiers in your queries.

For example, you might want to write such query:

```php
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\Identifier;
use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($writer instanceof Writer);

$writer->prepare(
    <<<SQL
    SELECT ? AS ? FROM ?
    SQL,
    [
        new ColumnName('foo', 'bar'),
        new Identifier('some.escaped.alias'),
        new TableName('bar', 'table_schema'),
    ]
);
```

A shortcut exists for typing expressions and enforce their conversion when
query is being built, the `?::TYPE` placeholder syntax.

Using the `?::TYPE` PostgreSQL-like cast will trigger the corresponding value
conversion from the `$arguments` array to be done while query is being written
by the writer instance.



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
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($writer instanceof Writer);

$writer->prepare(
    <<<SQL
    SELECT ?::column AS ?::identifier FROM ?::table
    SQL,
    [
        'bar.foo',
        'some.escaped.alias',
        'table_schema.bar',
    ]
);
```

will generate the following SQL code:

```sql
    select "bar"."foo" as "some.escaped.alias" from "table_schema"."bar"
```

:::warning
Typo errors in escaper names will not raise errors, but will be silently passed
as a value type hint instead.
:::

:::warning
Using the `::TYPE` syntax unpreceeded with the `?` character will be left as-is
within the query. This type cast syntax is PostgreSQL specific and you can use
it in your raw SQL.
:::

## Expressions instances

The placeholder is much more than a value placeholder, it can also be used to
place complex SQL expressions in a raw SQL string, for example:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($queryBuilder instanceof QueryBuilder);
assert($writer instanceof Writer);

$nestedSelect = $queryBuilder
    ->select('some_table')
    ->column('id')
;

$writer->prepare(
    <<<SQL
    select *
    from "other_table"
    where
        "some_id" not in ?
        and "some_value" is not ?
    SQL,
    [
        $nestedSelect,
        true,
    ]
)
```

This will result in the following SQL code:

```sql
select *
from "other_table"
where
    "some_id" not in (
        select id
        from "some_table"
    )
    and "some_value" is not ?
```

:::tip
Please note that the first placeholder is removed from the generated SQL
code and replaced with the generated SQL code corresponding to the given
select expression.

The resulting argument bag will have the correct number of parameters and
values matching the resulting SQL code.
:::

## Escape placeholder

In order to escape the `?` character, double it, hence the following
SQL query:

```sql
SELECT bar ?? foo WHERE baz = ?;
```

will be sent to the driver rewritten as such:

```sql
SELECT bar ? foo WHERE baz = 'your value';
```
