# Getting Started

All you need to know to start using *Query Builder*.

## Standalone setup

### 1. Install

Install it using composer:

```sh
composer require makinacorpus/query-builder
```

First of all, you need to chose your SQL dialect, this package provides
a few implementations:

 - **MariaDB** >= 10.0,
 - **MySQL** <= 5.7 and >= 8,
 - **PostgreSQL** >= 9.5,
 - **SQLlite** (all versions).

Most tested implementation is PostgreSQL.

### 2. Setup the query builder

```php
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\PostgreSQLWriter;
use MakinaCorpus\QueryBuilder\QueryBuidler;

/*
 * Escaper is the component that ties the query-builder with third-party
 * Database Access Layers, such as `doctrine/dbal` or simply `PDO`.
 */
$escaper = new StandardEscaper();

/*
 * Writer is the component that writes the SQL code which will take care
 * of supported RDMS dialects.
 */
$writer = new PostgreSQLWriter($escaper);

/*
 * User facade for you to build SQL queries.
 */
$queryBuilder = new QueryBuilder();
```

### 3. Write your query

```php
use MakinaCorpus\QueryBuilder\QueryBuidler;

$queryBuilder = new QueryBuilder();

$query = $queryBuilder
    ->select('users')
    ->column('id')
    ->column('name')
    ->column(
        $queryBuilder
            ->select('orders')
            ->columnRaw('count(*)')
            ->where('user_id', new Column('users.id')),
        'order_count'
    )
    ->columnRaw(
        <<<SQL
        (
            SELECT max("login")
            FROM "login_history"
            WHERE
                "user_id" = "users"."id"
            SQL
        )
        'last_login'
    )
    ->where('id', 'john.doe@example.com')
;
```

This is a simple one, but the query builder can help you for many things:

 - write CTE (`with "foo" as (select ...)` clauses,
 - handle many `JOIN` types, in `DELETE`, `SELECT` and `UPDATE` queries and
   generating them using the chosen SQL dialect,
 - write complex `WHERE` expressions,
 - properly escape identifiers and other string literals in generated SQL,
   security oriented unit tests exist for this,
 - replace user-given arbitrary parameters using the placeholder schema of
   your choice,
 - and much more!

### 4. Generate the SQL and execute it

Considering you have a DBAL of your own with the following method:

```php
interface MyDbal
{
    public function execute(string $sql, array $parameters): MyDbalStatement;
}
```

Then simply generate SQL and pass it along:

```php
use MakinaCorpus\QueryBuilder\QueryBuidler;

$prepared = $writer->prepare($query);

$myDbal->execute(
    $prepared->toString(),
    $prepared->getArguments()->getAll(),
);
```

If you need to convert PHP native values to SQL first, please see the
next chapter.

## Doctrine DBAL setup

### 1. Install

Install it using composer:

```sh
composer require makinacorpus/query-builder doctrine/dbal:^3.7
```

### 2. Setup the query builder

Setting it up is easier than standalone setup:

```php
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;

/*
 * Create or fetch your DBAL connection.
 */

$connection = DriverManager::getConnection([
    'driver' => 'pdo_pgsql',
    // ... driver options.
]);

/*
 * User facade for you to build SQL queries.
 */
$queryBuilder = new DoctrineQueryBuilder($connection);
```

:::tip
You don't need to specify the SQL dialect to use, it will be derived from
the `doctrine/dbal` connection automatically, without requiring any extra SQL
query to do so.
:::

### 3. Write your query and execute it

```php
$result = $queryBuilder
    ->select('users')
    ->column('id')
    ->column('name')
    ->column(
        $queryBuilder
            ->select('orders')
            ->columnRaw('count(*)')
            ->where('user_id', new Column('users.id')),
        'order_count'
    )
    ->columnRaw(
        <<<SQL
        (
            SELECT max("login")
            FROM "login_history"
            WHERE
                "user_id" = "users"."id"
            SQL
        )
        'last_login'
    )
    ->where('id', 'john.doe@example.com')
    ->executeQuery()
;
```

:::tip
`$result` is an instance of `Doctrine\DBAL\Result`.
:::

## PDO setup

### 1. Install

Install it using composer:

```sh
composer require makinacorpus/query-builder
```

### 2. Setup the query builder

Setting it up is easier than standalone setup:

```php
use MakinaCorpus\QueryBuilder\Bridge\Pdo\PdoQueryBuilder;

/*
 * Create or fetch your PDO connection.
 */

$connection = new \PDO('pgsql:...');

/*
 * User facade for you to build SQL queries.
 */
$queryBuilder = new PdoQueryBuilder($connection);
```

:::tip
You don't need to specify the SQL dialect to use, it will be derived from
the `PDO` connection automatically.
:::

### 3. Write your query and execute it

```php
$result = $queryBuilder
    ->select('users')
    ->column('id')
    ->column('name')
    ->column(
        $queryBuilder
            ->select('orders')
            ->columnRaw('count(*)')
            ->where('user_id', new Column('users.id')),
        'order_count'
    )
    ->columnRaw(
        <<<SQL
        (
            SELECT max("login")
            FROM "login_history"
            WHERE
                "user_id" = "users"."id"
            SQL
        )
        'last_login'
    )
    ->where('id', 'john.doe@example.com')
    ->executeQuery()
;
```

:::tip
`$result` is an instance of `\PDOStatement`.
:::

