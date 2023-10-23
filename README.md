# Query Builder

This is a driver-agnostic SQL query builder with advanced SQL language features.

Per default, it generates (almost) SQL standard compliant queries, almost
because some SQL features are implemented nowhere and we chose for some to
use common dialect instead.

It is based upon a fluent API, which makes basic SELECT, DELETE, INSERT,
UPDATE and MERGE queries easy to write, even if you don't really now SQL.

Under the hood, your queries are trees of Expression instances, and at any
place, any method, you may pass arbitrary expressions if the builder does
not support the feature you want to use: this means you can use all
features of your favorite RDBMS without worrying about this API limitations.

This API by itself is only a SQL string generator, in order to use it
transparently, a few bridges are provided:

 - `doctring/dbal:^3` query builder,
 - soon a `doctring/dbal:^4` query builder,
 - `PDO` query builder,
 - Symfony bundle will be available in a separate package.

# Getting started

## Standalone setup

### 1. Install

Simply:

```sh
composer require makinacorpus/query-builder
```

First of all, you need to chose your SQL dialect, this package provides
a few implementations:

 - MySQL <= 5.7 and MySQL >= 8,
 - PostgreSQL from 9.5 to current,
 - SQLlite.

Most tested implementation is PostgreSQL.

### 2. Setup the query builder

```php
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\PostgreSQLWriter;
use MakinaCorpus\QueryBuilder\QueryBuidler;

$escaper = new StandardEscaper();

$writer = new PostgreSQLWriter($escaper);

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
            ->where('user_id', new Column('users.id'))
        ,
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

```
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

A `doctrine/dbal` bridge is provided, please read the documentation.

## PDO setup

A `PDO` bridge is provided, please read the documentation.

## Configuring value placeholders

User-given PHP values will be replaced in generated SQL by placeholders
that allows you to use your driver own:

 - parameter passing and query preparation tooling,
 - parameter value convertion,
 - value security escaping.

This API does not aim to replace the security features of your database
access layer, but let you use it instead.

Per default, all user parameters are replaced using the `?` placeholder
generated SQL code, and legitimate `?` character usage is replaced by
doubling it i.e.: `??`.

This behavior is configurable, for example, when using the PDO defaut
parameter replacement, you can keep the default configuration.

If you need to use `ext-pgsql` instead, the API awaits parameters to
be numbered as such: `$1`, `$2`, ... For this, you can configure:

 - placeholder prefix, i.e. `$`,
 - placeholder start offset, i.e. `1`.

For this, when you create the `Escaper` instance, simply set this
in the constructor:

```php
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;

$escaper = new StandardEscaper('$', 1);
```

Then use the API as intended, for example:

```php
$queryBuilder
    ->select('foo')
    ->where('bar', 1)
    ->where('baz', 'hello')
```

And its equivalent written as raw SQL:

```php
use MakinaCorpus\QueryBuilder\Expression\Raw;

new Raw(
    <<<SQL
    select *
    from "bar"
    where
        "bar" = ?
        and "baz" = ?
    SQL,
    [1, 'hello']
);
```

would generate the following code:

```sql
select *
from "bar"
where
    "bar" = $1
    and "baz" = $2
```

Please note that for consistency and portability reasons, this API always
require that you identify value placeholder in raw SQL expressions using the
`?` placeholder, no matter which one will end in generated SQL code.

# Building documentation

Documentation is written using [VitePress](https://vitepress.dev).

For readers:

```sh
cd ./docs/
nvm use
npm ci
npm run docs:build
<your browser> ./.vitepress/dist/index.html
```

For developers:

```sh
cd ./docs/
nvm use
npm ci
npm run docs:dev
```

# Run tests

Most unit tests will work gracefuly by running directly PHPUnit:

```sh
composer install
vendor/bin/phpunit
```

For running bridges integration tests, it needs a complete environment with
some databases up and running, for this purpose a script that uses
`docker compose` is provided, yet it might not work gracefuly in all
environments:

```sh
composer install
./run-tests.sh
```

Please understand that the testing environment is currently at the prototype
stage, and will remain as-is until github actions CI is configured.

# Roadmap

## Short-term

 - [ ] Document everything!
 - [x] Add `doctrine/dbal` bridge for using it transparently.
 - [ ] Add a Symfony bundle for using it transparently along `doctrine/dbal`,
 - [ ] Setup CI via github actions.

## Long term

 - [ ] Restore the `makinacorpus/goat-query` PHP to SQL and SQL to PHP value
   converter, in its own package, independently from this one.
 - [ ] Implement more and more SQL features.

# History

This API is an almost complete rewrite, keeping the base design, using modern
PHP >= 8.2 features and much more unit tested of the query builder from the
`makinacorpus/goat-query` package.

Rationale is that we deeply used `makinacorpus/goat-query` as our primary
database access layer for some years, but we are shifting toward using more
commonly used tooling for future projects.

Doctrine DBAL query builder can do everything you would want to do, but its
API is unintuitive and written code using is hard to read. Moreover, it
doesn't implement modern SQL language features since its goal is mostly to
provide lowest common denominator for a huge number of RDBMS.
