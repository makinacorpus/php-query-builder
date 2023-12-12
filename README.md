# Query Builder

[![Coding standards](https://github.com/makinacorpus/php-query-builder/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/makinacorpus/php-query-builder/actions/workflows/coding-standards.yml) [![Static Analysis](https://github.com/makinacorpus/php-query-builder/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/makinacorpus/php-query-builder/actions/workflows/static-analysis.yml)  [![Documentation Status](https://readthedocs.org/projects/query-builder/badge/?version=stable)](https://query-builder.readthedocs.io/en/stable/?badge=stable)

This is a driver-agnostic SQL query builder with advanced SQL language features.

Per default, it generates (almost) SQL standard compliant queries, almost
because some SQL features are simply not implemented by any dialect we support
case in which we implement a custom variant.

It is based upon a fluent API, which makes basic SELECT, DELETE, INSERT,
UPDATE and MERGE queries easy to write, even if you don't really now SQL.

At any place the user can inject raw SQL in order to escape the query
builder own limitations.

This API by itself is only a SQL string generator, in order to use it
transparently, a few bridges are provided:

 - `doctrine/dbal:^3` bridge and query builder,
 - soon a `doctrine/dbal:^4` bridge and query builder,
 - `PDO` bridge and query builder,
 - Symfony bundle integrating with `doctrine/doctrine-bundle` is available in the
   [makinacorpus/query-builder-bundle](https://github.com/makinacorpus/query-builder-bundle)
   package.

# Getting started

Read the [documentation on read the docs](https://php-query-builder.readthedocs.io/en/latest/introduction/getting-started.html).

Overview [list of SQL supported features](https://php-query-builder.readthedocs.io/en/latest/introduction/features.html#).

See the [officially supported vendor dialects](https://php-query-builder.readthedocs.io/en/latest/introduction/getting-started.html#standalone-setup).

# Non-exhaustive feature list

 - **Common Table Expressions**:
    - `WITH "foo" AS (SELECT ...)`
 - **VALUES / Constant table expression**:
    - `WITH "foo" ("col1", "col2") AS (SELECT VALUES ...)`
    - `SELECT VALUES ... AS ("col1", "col2") `
    - `JOIN (VALUES ...) AS "foo"`, ...
 - **Aggregate function filter**:
    - Standard SQL: `SELECT SUM(foo) FILTER (WHERE <condition>)`
    - Emulation for non supporting RDBMS: `SELECT SUM(CASE WHEN <condition> THEN foo END)`
 - **Window functions**:
    - `SELECT SUM(foo) OVER (PARTITION BY... ORDER BY...)`
    - `SELECT SUM(foo) OVER (bar) FROM baz WINDOW bar AS (PARTITION BY... ORDER BY...)`
 - **RETURNING/OUTPUT**:
    - PostgreSQL: `UPDATE foo SET ... RETURNING foo, bar, baz`
    - SQL Server: `UPDATE foo SET ... OUTPUT DELETED.col1, INSERTED.col1`
 - **Complex WHERE condition builder**
 - **CASE WHEN ... THEN ... END**
 - **IF THEN emulated using CASE ... WHEN**
 - **ARRAY expression**:
    - `ARRAY[? ,? ?]`
 - **ROW expressions**:
    - Standard SQL: `ROW('foo', 2, ...)`
    - PostgreSQL composite types: `ROW('foo', 2, ...)::some_composite_type`
 - **Mixing complex data structures**:
    - `CAST(ROW('foo', CAST(ARRAY[1,2,3] AS bigint[])) AS some_composite_type)`
 - **CAST expressions**:
    - Standard SQL: `CAST(expression AS type)`
    - PostgreSQL: `expression::type`
 - **LIKE / SIMILAR TO**
    - `foo LIKE '%somevalue%'`
    - `foo SIMILAR TO '%somevalue%'`
 - **Update FROM JOIN**
 - **INSERT INTO ... VALUES ...**
 - **INSERT INTO ... SELECT ...**
 - **Arbitrary RAW SQL from user everywhere**
   - The builder doesn't support some X or Y advanced feature or specific
     dialect, then write your own SQL.
 - **Identifier escaping everywgere**
 - **User value conversion to SQL**

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
