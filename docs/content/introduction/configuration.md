# Configuration

Most pieces of this API will be auto-configured if used through a bridge.

Meanwhile when used as a standalone component, you may need to configure the
SQL dialect you need, as well as the escaping strategy required to adapt to
you database access layer or driver.

## Choose the SQL dialect

SQL dialect is handled and written by the `MakinaCorpus\QueryBuilder\Writer\Writer`
class. This default implementation will output standard compliant SQL. It will work
with any RDBMS understanding standard compliant SQL, such as PostgreSQL.

The only exception for PostgreSQL is for the `MERGE` queries, it is recommended to
use the PostgreSQL writer in order to support those properly.

The following implementations are provided:

 - `MakinaCorpus\QueryBuilder\Platform\Writer\MySQL8Writer` for MySQL >= 8.0 and MariaDB >= 10.0,
 - `MakinaCorpus\QueryBuilder\Platform\Writer\MySQLWriter` for MySQL <= 5.7,
 - `MakinaCorpus\QueryBuilder\Platform\Writer\PostgreSQLWriter` for PostgreSQL >= 9.5,
 - `MakinaCorpus\QueryBuilder\Platform\Writer\SQLiteWriter` for SQLite any versions,
 - `MakinaCorpus\QueryBuilder\Writer\Writer` for generated standard compliant SQL.

:::tip
You can write your own by extending the `MakinaCorpus\QueryBuilder\Writer\Writer` class.
:::

All you need to do is to create an instance of any of those for generating
your SQL queries.

## Configure standard escaper

Escaper is the component that ties the generated SQL to a concrete Database Access
Layer ou driver implementation. It will take care of proper identifier and other
symbols escaping, as well as replacing userland provided values to placeholders
for query methods.

Default escaper will provide escaping for identifiers and string literals in
an standard compliant SQL way. It is suitable for most RDBMS except MySQL and
derivatatives such as MariaDB.

Default implementation is `MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper`.

### Configuring the default escaper

While your write your SQL queries, you can, and probably will, provide userland
arbitrary values, such as:

```php
$queryBuilder
    ->select('some_table')
    ->where('some_column', "This is a value")
```

Those values will not be written in the output SQL, but extracted into a
`MakinaCorpus\QueryBuilder\ArgumentBag` instance by the writer.

Whenever values are found they will be replaced in the SQL code using a
placeholder. The placeholder value itself may vary depending upon the driver
you will use to execute queries, for example:

 - `PDO` uses `?` as value placeholder in SQL code,
 - `ext-pgsql` uses `$1`, `$2`, ... value placeholders in SQL code.

Both behaviours are possible using the default implementation, in order to
use a string constant placeholder, simply create the default escaper as
follows:

```php
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;

$escaper = new StandardEscaper('STRING_CONSTANT');
```

If you need numbered argument placeholders in the PostgreSQL fashion, then
proceed as such:

```php
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;

$escaper = new StandardEscaper('PREFIX', 1);
```

Where the second argument is the numbering start offset.

This should cover majority of use cases.
