# Database vendor version conditionals

## Introduction

When you write portable code, you might want to dynamically branch your code
depending upon the database server vendor name or version. This library exposes
a few helpers method for doing that gracefully.

:::info
Version string must always be a semantic version string, in the `x.y.z`
(major, minor, patch) form where `x`, `y` and `z` are integers.
You can reduce it to either `x.y` or simply `x` whenever it suits you,
missing digits will always be replaced by `0`.
:::

This section documents methods on the `MakinaCorpus\QueryBuilder\DatabaseSession`
interface. All bridges implement it.

## Compare database vendor name

Simply use the `DatabaseSession::vendorIs()` method, such as:

```php
use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Vendor;

\assert($session instanceof DatabaseSession);

if ($session->vendorIs(Vendor::MYSQL)) {
    // Write code for MySQL.
}
if ($session->vendorIs(Vendor::POSTGRESQL)) {
    // Write code for PostgreSQL.
}
if ($session->vendorIs('postgresql')) {
    // Variant with a user given raw string.
}
if ($session->vendorIs('mysql')) {
    // Variant with a user given raw string.
}
```

And more importantly, all vendor name comparison methods accept a value
array for checking if the vendor is any of:

```php
use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Vendor;

\assert($session instanceof DatabaseSession);

if ($session->vendorIs([Vendor::MARIADB, Vendor::MYSQL)) {
    // Write code for MariaDB and MySQL.
}
```

:::info
It is advised to use the `MakinaCorpus\QueryBuilder\Vendor` constants for
checking against the vendor name, yet is not mandatory: user given vendor
name is a raw string, which will be lowercased and reduced to only latin
alphabet characters prior to being compared against.

For example the `SQL Server 2022` value will be reduced to `sqlserver`.

Values will be compared against a set of known synonyms in order to be
tolerant to user conventions, though it is not advised to rely upon these
tolerances since the known synonyms are not part of the public API and
might change.
:::

## Compare database version

Simply use the `DatabaseSession::vendorVersionIs()` method, such as:

```php
use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Vendor;

\assert($session instanceof DatabaseSession);

if ($session->vendorVersionIs('1.2.3')) {
    // Server version is greater or equal to '1.2.3'.
}
if ($session->vendorVersionIs('1.2.3', '>=')) {
    // Server version is greater or equal to '1.2.3' (explicit operator).
}
if ($session->vendorVersionIs('1.2')) {
    // Server version is greater or equal to '1.2.0'.
}
if ($session->vendorVersionIs('1')) {
    // Server version is greater or equal to '1.0.0'.
}
if ($session->vendorVersionIs('1.2.3', '<')) {
    // Server version is less than '1.2.3'.
}
// ... See list of operators below.
```

Supported operators are:

 - `<`: server version is lower than given value,
 - `<=`: server version is lower or equal than given value,
 - `=`: server version is equal to given value,
 - `>=`: server version is greater or equal than given value,
 - `>`: server version is greater than given value.

:::info
Version comparison operators are raw user given strings, nevertheless
giving an invalid value will raise an exception.
:::

:::info
Later, composer version notation such as `~1.2.3` or `^1.2|^2.0` might be
implemented for finer conditions to be written by user.
:::

## Compare both at once

Common use case is to check both vendor name and version at once:

Simply use the `DatabaseSession::vendorIs()` method, such as:

```php
use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Vendor;

\assert($session instanceof DatabaseSession);

if ($session->vendorIs(Vendor::MYSQL, '8.0')) {
    // Server version is MySQL with version greater or equal to '8.0'.
}
if ($session->vendorIs(Vendor::MYSQL, '8.0', '>=')) {
    // Server version is MySQL with version greater or equal to '8.0' (explicit operator).
}
```

:::info
All operators accepted by the `vendorVersionIs()` methods are supported.
:::
