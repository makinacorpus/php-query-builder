# Samples usages

[[toc]]

This page will expose a few commonly used examples, but is not a complete
documentation. **Many more features exist** that you may discover either by
reading the documentation or by navigating directly into the code.

The Query Builder is built as a **fluent API**, fluent means that you can chain
almost every method call, exception being the methods that create new
objects that requires you to manipulate.

:::info
In all this page, The `$queryBuilder` variable will always be an instance of
`MakinaCorpus\QueryBuilder\DatabaseSession` or `MakinaCorpus\QueryBuilder\QueryBuilder`.
The `$result` variable will always be an instance of `MakinaCorpus\QueryBuilder\Result\Result`.
:::

## Simple `SELECT`

Selecting and manipulating data is as simple as:

```php
use App\Entity\User;
use MakinaCorpus\QueryBuilder\Result\ResultRow;
use MakinaCorpus\QueryBuilder\Where;
use Ramsey\Uuid\Uuid;

$result = $queryBuilder
    ->select('users')
    ->column('id')
    ->column('name')
    ->column('email')
    ->getWhere()
        ->isLike('email', '%@?', 'gmail.com')
    ->end()
    ->where('created_at', new \DateTime('2023-01-01 00:00:00'), '>')
    ->range(100)
    ->orderBy('created_at')
    ->executeQuery()
    ->setHydrator(fn (ResultRow $row) => new User(
        $row->get('id', Uuid::class),
        $row->get('name'),
        $row->get('id'),
    ))
;

foreach ($result as $user) {
    assert($user instanceof User);
    // ...
}
```

Which will generate the following SQL:

```sql
SELECT
    "id",
    "name",
    "email"
FROM "users"
WHERE
    "email" IS LIKE '?@gmail.com'
    AND "created_at" > '2023-01-01 00:00:00'
ORDER BY
    "created_at" DESC
LIMIT 100 OFFSET 0
```

:::tip
Here we use `Result::setHydrator()` and iterate over it. Nevertheless, the
`MakinaCorpus\QueryBuilder\Result\Result` interface is API compatible with
`doctrine/dbal` own `Doctrine\DBAL\Result` class, all its methods are
available for you to use.
:::

## Aggregate function

Basic aggregate function usage is a simple as calling the `columnAgg()` method:

```php
use App\Entity\User;
use MakinaCorpus\QueryBuilder\Result\ResultRow;
use MakinaCorpus\QueryBuilder\Where;
use Ramsey\Uuid\Uuid;

$actualAverageSalary = (int) $queryBuilder
    ->select('employee_salary')
    ->columnAgg('avg', 'salary', 'average_salary')
    ->whereRaw(
        '? BETWEEN ?::column AND ?::column',
        [
            $queryBuilder->expression()->currentTimestamp(),
            'date_start',
            'date_end',
        ]
    )
    ->groupByRaw('1')
    ->executeQuery()
    ->findOne()
;
```

Which will generate the following SQL:

```sql
SELECT
    avg("salary") AS "average_salary"
FROM "employee_salary"
WHERE
    current_timestamp BETWEEN "date_start" AND "date_end"
GROUP BY 1
```

:::tip
For more fun, we also introduced the special `?::column` token which will
force the parser to interpret the corresponding argument as a column name
and escape it properly into the generated SQL query.
:::

## Aggregate `FILTER (WHERE ...)`

This API supports the `FILTER (WHERE ...)` clause for aggregate functions,
and provide an all-dialects compatible fallback for platforms not supporing
it:

```php
use App\Entity\User;
use MakinaCorpus\QueryBuilder\Result\ResultRow;
use MakinaCorpus\QueryBuilder\Where;
use Ramsey\Uuid\Uuid;

$select = $queryBuilder
    ->select('employee_salary')
;

$select
    ->createColumnAgg('avg', 'salary', 'high_average')
    ->filter('salary', 1000, '>')
;

$select
    ->createColumnAgg('avg', 'salary', 'low_average')
    ->filter('salary', 1000, '<')
;

$result = $select
    ->columnAgg('avg', 'salary', 'all_average')
    ->groupByRaw('1')
    ->executeQuery()
;
```

Which will generate the following SQL for dialects supporting it:

```sql
SELECT
    avg("salary") FILTER (WHERE "salary" > 1000) AS "high_average",
    avg("salary") FILTER (WHERE "salary" < 1000) AS "low_average",
    avg("salary") AS "all_average",
FROM "employee_salary"
GROUP BY 1
```

Or as a fallback for all other:

```sql
SELECT
    avg(CASE WHEN "salary" > 1000 THEN "salary" ELSE NULL END) AS "high_average",
    avg(CASE WHEN "salary" < 1000 THEN "salary" ELSE NULL END) AS "low_average",
    avg("salary") AS "average_salary" AS "all_average",
FROM "employee_salary"
GROUP BY 1
```

:::info
This is completely transparent and will work with all supported platforms.
The only difference might be the server performance.

The `COUNT` average function is treated differently and also have its own
working fallback for all supported dialects.
:::

## Joining

Joining is a simple as:

```php
$result = $queryBuilder
    ->select('employe', 'emp')
    ->column('emp.id')
    ->column('sal.salary')
    ->join('employe_salary', 'sal.employe_id = emp.id', 'sal')
    ->executeQuery()
;
```

Which will generate the following SQL:

```sql
SELECT
    "emp"."id",
    "sal"."salary"
FROM "employee" AS "emp"
INNER JOIN "employe_salary" AS "sal"
    ON sal.employe_id = emp.id
```

You will notice that `'sal.employe_id = emp.id'` is injected as raw SQL, if you
need proper escaping, you can write:

```php
use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\Where;

$result = $queryBuilder
    ->select('employe', 'emp')
    ->column('emp.id')
    ->column('sal.salary')
    ->join(
        'employe_salary',
        ExpressionFactory::raw(
            '?::column = ?::column',
            ['sal.employe_id', 'emp.id']
        ),
        'sal'
    )
    ->executeQuery()
;
```

Or even:

```php
use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\Where;

$result = $queryBuilder
    ->select('employe', 'emp')
    ->column('emp.id')
    ->column('sal.salary')
    ->join(
        'employe_salary',
        fn (Where $where) => $where->isEqual(
            ExpressionFactory::column('sal.employe_id'),
            ExpressionFactory::column('id', 'emp'),
        ),
        'sal'
    )
    ->executeQuery()
;
```

Which will all generate the following SQL code:

```sql
SELECT
    "emp"."id",
    "sal"."salary"
FROM "employee" AS "emp"
INNER JOIN "employe_salary" AS "sal"
    ON "sal"."employe_id" = "emp"."id"
```

:::tip
Default join mode is `INNER JOIN`, but you also may call `leftJoin()` or `innerJoin()`,
or simply `join()` passing the `$mode` argument which allows more alternatives such
as `LEFT OUTER`, `RIGHT OUTER`, `NATURAL`, etc...
:::

:::tip
`MakinaCorpus\QueryBuilder\ExpressionFactory::raw()` is a shortcut to `new MakinaCorpus\QueryBuilder\Raw()`,
and can also be called using `$queryBuilder->expression()->raw()`, all of this methods
share the same signature, and will give the exact same result.
:::

:::tip
Whenever you express a column identifier, if the string contains a dot then
it will splited and formated as `"table"."column"`. You may disable this
automatic detection if your column name contains a dot by specifying
the `$noAutomaticNamespace` to `true` in the column factory method.
:::

## Cross join with nested select

Something you might want to do is using nested `SELECT` expressions for joining:

```php
use MakinaCorpus\QueryBuilder\Where;

$result = $queryBuilder
    ->select('employe', 'emp')
    ->column('emp.id')
    ->column('sal.salary')
    ->from(
        $queryBuilder
            ->select('employe_salary')
            ->column('employe_id', 'emp_id')
            ->column('salary'),
        'sal'
    )
    ->whereRaw('?::column = ?::column', ['emp.id', 'sal.emp_id'])
    ->executeQuery()
;
```

Which will generate the following SQL:

```sql
SELECT
    "emp"."id",
    "sal"."salary"
FROM
    "employee" AS "emp",
    (
        SELECT
            "employe_id" AS "emp_id",
            "salary"
        FROM "employe_salary"
    ) AS "sal"
WHERE
    "emp"."id" = "sal"."emp_id"
```

:::tip
As you can see, almost every method has a `*Raw()` alternative to allow writing
raw SQL instead of using the builder.
:::

## Window functions

Simple example that ranks rows by random order:

```php
$select = $queryBuilder
    ->select('some_table')
;

$select
    ->createColumnAgg(
        function: 'row_number',
        alias: 'rownum'
    )
    ->over(
        orderBy: ExpressionFactory::random()
    )
;

$result = $select->executeQuery();
```

That will generate the following SQL code:

```sql
SELECT
    row_number() OVER (ORDER BY random()) AS "rownum"
FROM "some_table"
```

For dialects that support it, you may also move the `OVER()` clause content
into the `FROM` as such:

```php
$select = $queryBuilder
    ->select('some_table')
    ->window(
        alias: 'my_window',
        orderBy: ExpressionFactory::random()
    )
;

$select
    ->createColumnAgg(
        function: 'row_number',
        alias: 'rownum'
    )
    ->overWindow('my_window')
;

$result = $select->executeQuery();
```

Which will result in the following SQL code:

```sql
SELECT
    row_number() OVER ("my_window") AS "rownum"
FROM "some_table"
WINDOW "my_window" AS (ORDER BY random())
```

Be aware that the generated SQL code will remain the same for all dialects
and this will simply cause error for servers that don't support it.

:::info
Some dialects don't support `ORDER BY` without `PARTITION BY`, case in which the
window will be generated this way: `row_number() OVER (PARTITION BY 1 ORDER BY random())`.
:::

:::info
`random()` expression is not standard SQL and is used by PostgreSQL and SQLite,
for others, it will generate using their respective dialects.
:::

:::tip
Per default, `$paritionBy` and `$orderBy` parameters will be interpreted as column
names, in order to escape from this, you may use the expression factory to create
any other expression.
:::

## Nested select

Any builder parameter can be replaced at any time by any `MakinaCorpus\QueryBuilder\Expression`
instance, this includes `MakinaCorpus\QueryBuilder\Query` implementations as well.

For example:

```php
$queryBuilder
    ->select('user')
    ->column('user.name')
    ->column(
        $queryBuilder
            ->select('user_message')
            ->columnAgg('count')
            ->where('unread')
            ->whereRaw('?::column = ?::column', ['user_message.user_id', 'user.id']),
        'message_count'
    )
;
```

Will generate the following SQL code:

```sql
SELECT
    "user"."name",
    (
        SELECT
            COUNT(*)
        FROM "user_message"
        WHERE
            "unread"
            AND "user_message"."user_id" = "user"."id"
    ) AS "message_count"
FROM "user"
```

:::tip
You can inject arbitrary `Select` object into JOIN, WITH, SELECT, FROM, and
many more places, everywhere you need it.

**The query builder does not validate your SQL** but helps in preventing you
being stuck in its own limitations. Whenever you inject any expression at any
place, the query builder simply format it where it is.
:::

:::info
Note that extra parenthesis are automatically added whenever the query builder
detects that it requires it to deambiguate statements.

It may sometime fail in detect the right moment to do it, you can either:
 - add the parenthesis yourself using a raw expression embedding the nested expression,
 - **write an issue in our issue tracker with example code to reproduce it**.
:::

:::warning
Even if the query builder allows you to do it, injecting a `Delete`, `Insert`, `Merge` or
`Update` query as a nested query makes no sense and will absolutely raise errors on
the server side.

Yet for a nice moment of fun, if you wish, you can try.
:::

## Where clause

Boolean expressions can be written using the `MakinaCorpus\QueryBuilder\Where` class,
which can be arbitrarily instanciated at any moment and be passed as any expression
in any method.

The `Where` class acts as a specification pattern implementation, and give numerous
helper methods for adding various comparison expressions.

### Simple comparison

All of `Delete`, `Select` and `Update` allows a `WHERE` clause, which enable
the following syntax:

```php
$query->where('some_column', 'some_value');
```

Will give the following SQL:

```sql
WHERE "some_column" = 'some_value'
```

Additionnaly, you can use a third parameter `$operator` which allows you to
shortcut other expressions:

```php
$query->where('some_column', 'some_value', '<=');
```

Will give the following SQL:

```sql
WHERE "some_column" <= 'some_value'
```
:::warning
Using it this way, operator is considered as raw SQL and may lead to SQL injection.
:::

### Using fluent methods

`Where` class provide numerous comparison functions that will help you write faster
code without knowing the exact comparison or operator syntax:

```php
$query
    ->getWhere()
        ->exists('SELECT 1')
        ->isEqual('foo', 2)
        ->isBetween('bar', 3, 4)
        ->isLike('baz', 'foo%')
        ->isGreaterThan('fizz', 5)
        ->isNotIn('buzz', [7, 8, 9])
    ->end()
    // Here you returned to the original query and can continue chaining.
```

Which will generate the following SQL:

```SQL
WHERE
    EXISTS (
        SELECT 1
    )
    AND "foo" = 2
    AND "bar" BETWEEN 3 AND 4
    AND "baz" IS LIKE 'foo%'
    AND "fizz" > 5
    AND "buzz" NOT IN (
        7, 8, 9
    )
```

You can even nest `AND` or `OR` clauses:

```php
$query
    ->getWhere()
        ->exists('SELECT 1')
        ->and()
            ->isEqual('foo', 2)
            ->isBetween('bar', 3, 4)
        ->end()
        ->or()
            ->isGreaterThan('fizz', 5)
            ->isNotIn('buzz', [7, 8, 9])
        ->end()
    ->end()
    // Here you returned to the original query and can continue chaining.
```

Which will generate the following SQL:

```SQL
WHERE
    EXISTS (
        SELECT 1
    )
    AND (
        "foo" = 2
        AND "bar" BETWEEN 3 AND 4
    )
    AND (
        "fizz" > 5
        OR "buzz" NOT IN (
            7, 8, 9
        )
    )
```

:::warning
Your IDE will be tricked by the `end()` method for properly chaining, but for this
to work, we had to create as many classes as depth dimensions we support:
**chaining depeer that 2 levels will break IDE autocompletion** yet the code will
execute correctly.
:::

## Inserting arbitrary data

Inserting data is as easy as selecting data:

```php
$affectedRowCount = $queryBuilder
    ->insert('user')
    ->columns([
        'id',
        'name',
        'email',
    ])
    ->values([
        Uuid::uuid4(),
        'John Smith',
        'john.doe@example.com',
    ])
    ->values([
        Uuid::uuid4(),
        'Jane Doe',
        'jane.doe@example.com',
    ])
    ->executeStatement()
;
```

Will generate the following SQL:

```sql
INSERT INTO "user" (
    "id",
    "name",
    "email",
)
VALUES (
    'fcd45cac-4787-45ad-8d96-3db94f906858', 'John Smith', 'john.doe@example.com'
), (
    '203fd391-8d35-4c87-85ba-4ff5470693a7', 'Jane Doe', 'jane.doe@example.com'
)
```

You may omit the `columns()` call and set array keys instead:

```php
$affectedRowCount = $queryBuilder
    ->insert('user')
    ->values([
        'id' => Uuid::uuid4(),
        'name' => 'John Smith',
        'email' => 'john.doe@example.com',
    ])
    ->values([
        'id' => Uuid::uuid4(),
        'name' => 'Jane Doe',
        'email' => 'jane.doe@example.com',
    ])
    ->executeStatement()
;
```

By extension, this also mean that you can use this query builder in highly
dynamic code, such as for exemple:

```php
$insert = $queryBuilder->insert('user');

$some = false;
foreach (some_external_data_source() as $data) {
    $some = true;
    $insert->values([
        'id' => Uuid::uuid4(),
        'name' => $data['name'],
        'email' => $data['email'],
    ]);
}

if ($some) {
    $insert->executeStatement();
}
```

:::warning
When passing array with keys to `values()`, only the first call to `values()` will
determine the column names for the insert query, following `values()` calls keys will
be ignored.

In case of column count mismatch between values, an exception will be raised before
the SQL code being generated.
:::

:::tip
You can also use `WITH` (CTE), `FROM` and `JOIN` to write complex queries.
:::

:::tip
Mutation queries don't return any rows, except when you use a `RETURNING|OUTPUT` clause,
using `executeStatement()` instead of `executeQuery()` will return the affected row
count if the brige allows it.
:::

## Inserting data from select

You can also very easily `INSERT` from a `SELECT` query:

```php
$affectedRowCount = $queryBuilder
    ->insert('user')
    ->columns([
        'id',
        'name',
        'email',
    ])
    ->query(
        $queryBuilder
            ->select('user_pending_approval')
            ->columnRaw('gen_random_uuid()')
            ->column('name')
            ->column('email')
            ->where('approved', true)
    )
    ->executeStatement()
;
```

Which will generate the following SQL:

```sql
INSERT INTO "user" (
    "id",
    "name",
    "email",
)
SELECT
    gen_random_uuid(),
    "name",
    "email"
FROM "user_pending_approval"
WHERE
    "approved" = true
```

:::tip
You can also use `WITH` (CTE), `FROM` and `JOIN` to write complex queries.
:::

:::tip
Here for demonstration purpose, we wrote `->columnRaw('gen_random_uuid()')`
which calls a PostgreSQL specific method, yet another way of writing raw
SQL statement pretty much everywhere.
:::

:::info
Interesting fact that internally, `INSERT ... VALUES` is the same as `INSERT ... SELECT`
except that the `Query` instance being built is an instance of
`MakinaCorpus\QueryBuilder\Expression\ConstantTable` which reprensents the standard
`VALUES (...), [...]` SQL expression.
:::

## Upserting data

:::warning
This API doesn't support the `MERGE` query yet, it falls back on custom
dialect `INSERT ... [ON CONFLITC|ON DUPLICATE|...]` variants.

Behavior may change depending upon the platform.
:::

As said in the note upper, the query builder will only implement simple case
with an API built around PostgreSQL `ON CONFLICT (key1, ...)` syntax, but which
also works with some other dialects:

```php
$affectedRowCount = $queryBuilder
    ->merge('user')
    ->setKey(['email'])
    ->onConflictUpdate()
    ->columns([
        'name',
        'email',
    ])
    ->values([
        'id' => Uuid::uuid4(),
        'name' => 'John Smith',
        'email' => 'john.doe@example.com',
    ])
    ->values([
        'id' => Uuid::uuid4(),
        'name' => 'Jane Doe',
        'email' => 'jane.doe@example.com',
    ])
    ->executeStatement()
;
```

Which will generate the following SQL for PostgreSQL:

```sql
INSERT INTO "user" (
    "id",
    "name",
    "email",
)
VALUES (
    'fcd45cac-4787-45ad-8d96-3db94f906858', 'John Smith', 'john.doe@example.com'
), (
    '203fd391-8d35-4c87-85ba-4ff5470693a7', 'Jane Doe', 'jane.doe@example.com'
)
ON DUPLICATE KEY ("email")
    DO UPDATE SET
        "id" = EXCLUDED."id",
        "name" = EXCLUDED."name"
        "email" = EXCLUDED."email",
```

And the following SQL for MariaDB and MySQL:

```sql
INSERT INTO "user" (
    "id",
    "name",
    "email",
)
VALUES (
    'fcd45cac-4787-45ad-8d96-3db94f906858', 'John Smith', 'john.doe@example.com'
), (
    '203fd391-8d35-4c87-85ba-4ff5470693a7', 'Jane Doe', 'jane.doe@example.com'
) AS "new"
ON DUPLICATE KEY UPDATE
    "id" = "new"."id",
    "name" = "new"."name"
    "email" = "new"."email",
```

:::warning
The current implementation does not allow you to specify which columns will be
update in case of conflict, this feature will be added in a near future.
:::

:::tip
You can also use `WITH` (CTE), `FROM` and `JOIN` to write complex queries.
:::

:::info
The SQL writer implement is already able to partially write `MERGE` queries,
which are long supported by SQL Server and recently supported by PostgreSQL:
it is not completed yet and is a planned feature.
:::

## Updating data

Updating data is as simple as:

```php
$affectedRowCount = $queryBuilder
    ->update('user')
    ->set('login_latest', new \DateTimeImmutable('now'))
    ->set(
        'login_count',
        $queryBuilder
            ->expression()
            ->raw(
                '?::column + 1',
                ['login_count']
            )
    )
    ->where('id', 'some_id')
    ->executeStatement()
;
```

Which will generate the following SQL:

```sql
UPDATE "user"
SET
    "login_lastest" = '2023-12-04 13:12:00',
    "login_count" = "login_count" + 1
WHERE
    "id" = 'some_id'
```

:::tip
You can also use `WITH` (CTE), `FROM` and `JOIN` to write complex queries.
:::

## Deleting data

Deleting data is as simple as:

```php
$affectedRowCount = $queryBuilder
    ->delete('user')
    ->where('id', 'some_id')
    ->executeStatement()
;
```

Which will generate the following SQL:

```sql
DELETE FROM "user"
WHERE
    "id" = 'some_id'
```

:::tip
You can also use `WITH` (CTE), `FROM` and `JOIN` to write complex queries.
:::

## Returning mutated data

PostgreSQL has a specific `RETURNING` clause, SQL Server has the equivalent `OUPUT` clause,
both allows you to return either:

- rows after mutation with SQL Server,
- rows before or after mutation with SQL Server,

from mutation queries (ie. `DELETE`, `INSERT`, `MERGE` and `UPDATE` queries).

This API only supports returning rows after mutation, using the `returning()` method:

```php
$result = $queryBuilder
    ->delete('user')
    ->where('id', 'some_id')
    ->returning('id')
    ->returning('email')
    ->executeQuery()
;
```

Which will generate the following SQL with PostgreSQL:

```sql
DELETE FROM "user"
WHERE
    "id" = 'some_id'
RETURNING
    "id",
    "email"
```

And which will generate the following SQL for SQL Server:

```sql
DELETE FROM "user"
WHERE
    "id" = 'some_id'
OUTPUT
    "id",
    "email"
```

:::info
When using SQL Server `OUTPUT` close, you can identify columns as
`DELETED."foo"` or `INSERTED."foo"`, we don't support that syntax. Not specifying
`DELETED` or `INSERTED` will implicitely translate to `INSERTED`.
:::

## Escaper from builder: raw SQL

At any place, everywhere, you can happend raw SQL whenver this query builder
does not support a feature. This is one of the most important features of the
query builder.

See the [arbitrary SQL injection documentation](../query/raw).
