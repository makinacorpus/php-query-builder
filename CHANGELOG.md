# Changelog

## 1.6.1

* [fix] Fix MySQL 5.7 `decimal` and `float` type cast.

## 1.6.0

* [feature] ⭐️ Add `MakinaCorpus\QueryBuilder\BridgeFactory` for creating
  standalone connections.
* [fix] Better version compare algorithm, with less erroneous edge cases.
* [deprecation] ⚠️ Renamed `MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder`
  to `MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineBridge`.
* [deprecation] ⚠️ Renamed `MakinaCorpus\QueryBuilder\Bridge\Pdo\PdoQueryBuilder`
  to `MakinaCorpus\QueryBuilder\Bridge\Pdo\PdoBridge`.

## 1.5.5

* [fix] Handle `mysqli` in `Dsn` class.
* [fix] Handle `:memory:` in `Dsn` class.
* [fix] Better filename detection in `Dsn` class.

## 1.5.4

* [feature] Add `MakinaCorpus\QueryBuilder\Dsn` class as a public API for third
  party usage, with no internal use yet.

## 1.5.3

* [internal] Add `MakinaCorpus\QueryBuilder\Bridge\Mock\MockQueryBuilder` for
  third-party library or project unit testing.

## 1.5.2

* [fix] Add `doctrine/dbal:<3.7` to composer `conflict`.

## 1.5.1

* [feature] ⚠️ Move `Bridge::close()` method into the `DatabaseSession`
  interface. Add `DatabaseSession::connect()` method for reopening
  connection if closed.

## 1.5.0

The goal of this release is to hide the bridge concept from final user facing
API. User are encouraged to type the database connection or query builder using
the `MakinaCorpus\QueryBuilder\DatabaseSession` interface.

Warning: this release brings a few backward compatibility breaks, regarding
exception classes namespaces. Fixing your code is trivial, but required.

* [feature] Move `Bridge::getSchemaManager()` method into the `DatabaseSession`
  interface. Bridges should now never be used as a public API for end users.
* [bc] ⚠️ Move all exception classes from the `MakinaCorpus\QueryBuilder\Error\Bridge`
  namespace into the `MakinaCorpus\QueryBuilder\Error\Server` namespace.
* [bc] ⚠️ Rename `FunctionalTestCaseTrait::getBridge()` to
  `FunctionalTestCaseTrait::getDatabaseSession()`.
* [internal] Change PHPstan level from 3 to 5.

## 1.4.0

* [feature] ⭐️ Add `DatabaseSession::getVendorName()`, `DatabaseSession::getVendorVersion()`,
  `DatabaseSession::vendorIs()` and `DatabaseSession::vendorVersionIs()` methods.
* [feature] ⭐️ Rename the `Platform` class to `Vendor`..
* [deprecation] ⚠️ Deprecate `Bridge::getServerFlavor()`, `Bridge::getServerVersion()`,
  `Bridge::isVersionLessThan()` and `Bridge::isVersionGreaterOrEqualThan()` methods.
* [deprecation] ⚠️ Deprecate the `Platform` class.
* [fix] Fix table creation with an identity in SQLite.

## 1.3.3

* [feature] ⭐️ Experimental table index list in schema manager.
* [internal] Enable SQLite via PDO in unit tests.

## 1.3.2

* [feature] ⭐️ Experimental identity column handling in schema manager.
* [fix] Regression in schema manager.

## 1.3.1

* [feature] ⭐️ `$database` parameter on schema manager methods is now always optional.

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
