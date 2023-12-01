# Query basics

## Create a query

A query created from the query builder:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder->select($table, $alias);
$update = $queryBuilder->update($table, $alias);
$merge = $queryBuilder->merge($table);
$delete = $queryBuilder->delete($table, $alias);
```

## Generated SQL

**Generated SQL is SQL-92 standard compliant per default**, along with a few
variations from SQL 1996, 1999, 2003, 2006, 2008, 2011 when implemented. For SQL
servers that don't play well with SQL standard, drivers will fix the SQL query
formatting accordingly by themselves.

:::info
Depending on the database server, some constructs might not work (for example MySQL
does not support WITH or RETURNING statements): in most cases, it will fail while
during query execution in tyhe RDBMS side.
:::

## Generated SQL is arbitrary

:::tip
Validity of the SQL you build with the query builder is never validated, by design.

**Any method parameter in the whole builder can be arbitrary `MakinaCorpus\QueryBuilder\Expression`
instance** including the [raw SQL expression](raw).

When an expression object is given anywhere, the query writer will simply format
it at the exact place it was given.
:::

This includes table and column names which are not validated during query building:
you can always write arbitrary identifiers, they will be left untouched within the
generated SQL.

This explicitely allows you to go beyond the query builder capabilities and write
custom or specific arbitrary SQL.

:::warning
**Never allow arbitrary user values to pass down as raw SQL string**:
since they are not properly escaped, they represent a security risk.

**Keep them for edge cases the builder can't do**.
:::

The `MakinaCorpus\QueryBuilder\Expression\Raw` object allows you to pass arbitrary
parameters that must refer to [parameters placehoders](/query/placeholder)
within the expression arbitrary SQL string, example usage on a select query adding
an arbitrary raw expression to the where clause:

```php
// WHERE COUNT("comment") > 5
$select->whereRaw('COUNT("comment") > ?', [5]);
```

Parameter placeholders will be gracefully merged to the others in their
rightful respective order when SQL will be generated.

See the [arbitrary SQL injection documentation](../query/raw).

## Expression formatting examples

:::tip
There are many different expression implementations, please read the
[feature matrix](../introduction/features) for an exhaustive list.
:::

### Raw

This expression allows to write arbitrary unvalidated SQL.

```php
use MakinaCorpus\QueryBuilder\Expression\Raw;

// Create a raw expression
new Raw('count(*)');

// Create a raw expression with arguments
new Raw('sum(foo.column1) = ?', [12]);
```

### ColumnName

This expression allows you to identify a column, which will be properly escaped
in the generated SQL.

#### Simple example

```php
use MakinaCorpus\QueryBuilder\Expression\ColumnName;

new ColumnName('some_column');
```

Will be formatted as:

```sql
"some_column"
```

#### With a table alias (implicit)

```php

use MakinaCorpus\QueryBuilder\Expression\ColumnName;

new ColumnName('some_column.some_table');
```

Will be formatted as:

```sql
"some_table"."some_column"
```

#### With a table alias (explicit)

```php
use MakinaCorpus\QueryBuilder\Expression\ColumnName;

new ColumnName('some_column', 'some_table');
```

Will be formatted as:

```sql
"some_table"."some_column"
```

#### If you need to escape dot

```php
use MakinaCorpus\QueryBuilder\Expression\ColumnName;

new ColumnName('some.column', 'some.table');
```

Will be formatted as:

```sql
"some.table"."some.column"
```

### TableName

This expression allows you to identify a table, table, constant table with
alias, WITH statement.

#### Simple example

```php

use MakinaCorpus\QueryBuilder\Expression\TableName;

new TableName('some_table');
```

Will be formatted as:

```sql
"some.table"
```

#### With a table alias

```php
use MakinaCorpus\QueryBuilder\Expression\TableName;

new TableName('some_table', 'foo');
```

Will be formatted as:

```sql
"some.table" as "foo"
```

#### With a schema (implicit)

```php
use MakinaCorpus\QueryBuilder\Expression\TableName;

new TableName('my_schema.some_table', 'foo');
```

Will be formatted as:

```sql
"my_schema"."some_table" as "foo"
```

#### With a schema (explicit)

```php
use MakinaCorpus\QueryBuilder\Expression\TableName;

new TableName('some_table', 'foo', 'my_schema');
```

Will be formatted as:

```sql
"my_schema"."some_table" as "foo"
```

#### If you need to escape dot

```php
use MakinaCorpus\QueryBuilder\Expression\TableName;

new TableName('some.table', 'some.alias', 'my.schema');
```

Will be formatted as:

```sql
"my.schema"."some.table" as "foo"
```

### Value

Represents a raw value. **You will need this when the converter is unable
to find the appropriate type to convert to**, for example when you need
to store `json` or `jsonb` or an SQL `ARRAY`.

It will pass the type cast whenever necessary in queries, allowing the
converter to deambiguate values types.

:::tip
Value conversion and representation in SQL is done by the [converter](../converter/converter).
:::

#### Simple exemple

```php
use MakinaCorpus\QueryBuilder\Expression\Value;

new Value(12);
```

Will always be formatted as in the generated SQL code as a placeholder:

```sql
?
```

Type will be dynamically guessed by the converter as being `int`, converted
then sent as an argument to the underlaying database access layer.

#### With a type

```php
use MakinaCorpus\QueryBuilder\Expression\Value;

new Value(12, 'int');
```

Type will be treated as an `int` directly by the converter, which prevent it
from doing a dynamic lookup and is more performant.

#### JSON

```php
use MakinaCorpus\QueryBuilder\Expression\Value;

new Value(['foo' => 'bar', 'baz' => [1, 2, 3]], 'json');
```

Value will simply be converted to JSON and sent as-is.

#### ARRAY

```php
use MakinaCorpus\QueryBuilder\Expression\Value;

new Value([1, 2, 3], 'int[]');
```

And will be converted as:

```sql
ARRAY[1, 2, 3]
```
