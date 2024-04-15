# Changelog

## 1.3.0

SQL Server is now fully supported by the schema manager, this is still in an
experimental state.

Schema manager can now only work in current session connected database due to
some vendor-specific vendor restrictions.

Lots of internal refactor and fixes. The `MakinaCorpus\QueryBuilder\QueryExecutor`
interface is now know as the `MakinaCorpus\QueryBuilder\DatabaseSession` and is
meant to be used directly for users. It now holds `getCurrentDatabase(): string`
and `getDefaultSchema(): string` methods for getting some session context
information.

* [feature] ⭐️ Add SQL Server schema manager support.
* [feature] ⭐️ Add `CurrentDatabase` and `CurrentSchema` expression.
* [feature] ⭐️ Add `getCurrentDatabase()` and `getDefaultSchema()` methods on the `DatabaseSession` interface.
* [feature] ⭐️ Add `doctrine/dbal:^4.0` support
* [bc] ⚠️ `QueryExecutor` is now known as `DatabaseSession`
* [internal] Lots of missing unit tests added.

## 1.2.0

Main feature of 1.2.0 is an internal complete rewrite of SQL data type
handling. Types are now represented by the `MakinaCorpus\QueryBuilder\Type\Type`
class instances instead of a bare string. Each vendor can convert transparently
original user-given SQL type to a vendor-specific one. For example, SQL standard
`timestamp` type will become `datetime` in MySQL and `datetime2` in SQL Server.

* [feature] ⭐️ Graceful transparent SQL data type conversion to vendor-specific types (#20).
* [feature] ⭐️ Date interval support, with dowgrade for vendors that do not support the `interval` type (#8).
* [feature] ⭐️ SQL array to PHP array conversion fixes and PHP array to SQL array conversion.
* [fix] Fix possible PHP 8.3 crash.
* [internal] Add SQL Server 2022 in test matrix.
* [internal] Add `phpbench/phpbench` dependency along some basic benchmarks, see BENCHMARK.md file.

## 1.1.0

Main feature of 1.1.0 is the addition of a brand new schema alteration API.
This will remain marked as experimental and is subject to breaking changes
until at least all vendors are supported.

* [feature] ⭐️ Schema manager.
* [feature] ⭐️ SQL array to PHP array conversion for results.
* [fix] PDO cursors are forward-only per default, allowing almost memory-free result iteration (#2).
* [fix] PDO testing connexion are made persistent to avoid "too many connection" errors while tests are running.
* [fix] Fetching result row column matches names case-insentively, some vendors will uppercase schemata names, some other do not.
* [internal] Many `dev.sh` script and `docker-compose.yaml` fixes.

## 1.0.3

* [fix] MySQL Anonymization process - Fix join id index creation (#89)

## 1.0.2

* [fix] Functional and unit test base classes are now partially in the `src/` folder for third parties to be able to use it.

## 1.0.1

* [fix] Documentation fixes.

## 1.0.0

Initial release.
