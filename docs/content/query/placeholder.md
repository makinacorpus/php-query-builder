# Parameters placeholders

## Value placeholder and typing

Independently from the final database driver, all parameters within arbitrary SQL
must be written using the `?` placeholder in raw SQL:

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

See the [data types matrix](/query/datatype) for available types.

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

## Placeholder and expressions

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

## Escape placholder

In order to escape the `?` character, double it, hence the following
SQL query:

```sql
SELECT bar ?? foo WHERE baz = ?;
```

will be sent to the driver rewritten as such:

```sql
SELECT bar ? foo WHERE baz = 'your value';
```
