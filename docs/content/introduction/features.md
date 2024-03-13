---
outline: false
aside: false
layout: doc
---

# Features matrix

The exposed feature matrix is incomplete and grows over time.

In next tables, you will see the following legend:

 - *Yes* means the feature is supported, and has no specific dialect.
 - *Warning*  means the feature is supported as standard, but some advanced forms may raise error on the RDMS side.
 - *Dialect* means there is a specific dialect implemented.
 - *Downgrade* when a less efficient with feature parity variant is used because the platform does not implement it.
 - *Planned* when a feature is known to work but not yet implemented or tested.
 - *No* Means not implemented, sometime implemented but not officially supported.

## Basic SQL syntax

:::info
This describes the basic features of the query builder you can reach by chaining its methods.
:::

| Feature | MariaDB | MySQL | PostgreSQL | SQLite | SQL Server | Notes |
|:--------|:-------:|:-----:|:----------:|:------:|:----------:|:------|
| `AGGREGATE(...)` | Yes | Yes | Yes | Yes | Yes | - |
| `AGGREGATE() FILTER(...)` | Downgrade | Downgrade | Yes | No | Downgrade | For all other than PostgreSQL, a fallback that uses `CASE ... END` replaces the `FILTER` clause. |
| `AGGREGATE() OVER(...)` | Yes | Yes | Yes | Yes | Yes | In some cases, `PARITION BY 1` is automatically added when `ORDER BY` is present. |
| `DELETE FROM ... [JOIN] ...` | Dialect | Dialect | Yes | Yes | Yes | First `JOIN` is automatically converted to a cross join in `FROM` for most implementations. |
| `DELETE` | Yes | Yes | Yes | Yes | Yes | - |
| `INSERT IGNORE` | Downgrade | Downgrade | Dialect | Planned | Planned | MariaDB and MySQL can't target a particular constraint violation. |
| `INSERT SELECT` | Yes | Yes | Yes | Yes | Yes | - |
| `INSERT UPDATE` | Downgrade | Downgrade | Dialect | Planned | Planned | MariaDB and MySQL can't target a particular constraint violation. |
| `INSERT VALUES` | Yes | Yes | Yes | Yes | Yes | - |
| `JOIN` | Yes | Yes | Yes | Yes | Yes | - |
| `MERGE` | No | No | Planned | No | Planned | - |
| `RETURNING\|OUTPUT` | No | No | Dialect | No | Dialect | SQL Server differenciate rows before and after mutation, only rows after mutation are handled. |
| `SELECT DISTINCT` | Yes | Yes | Yes | Yes | Yes | - |
| `SELECT WINDOW AS (...)` | No | No | Yes | No | No | Only PostgreSQL seems to support it, yet we generate it for all. |
| `SELECT` | Yes | Yes | Yes | Yes | Yes | - |
| `UPDATE FROM ... [JOIN] ...` | Dialect | Dialect | Yes | Yes | Yes | First `JOIN` is automatically converted to a cross join in `FROM` for most implementations. |
| `UPDATE` | Yes | Yes | Yes | Yes | Yes | - |
| `VALUES (...), [...]` | Warning | Yes | Yes | Yes | Yes | MariaDB doesn't support `VALUES` aliasing, using it will raise RDMS errors. |
| `WITH` | Yes | Yes | Yes | Yes | Yes | There is no dialect syntactic differences. |
| `WITH RECURSIVE` | Planned | Planned | Planned | Planned | Planned | - |

## Various SQL expressions

Here is an incomplete list of supported arbitrary SQL expressions you can inject
anywhere during query building. Most of those are standard or implemented in all
supported databases.

:::info
Factory method in the following table refers to methods of the `MakinaCorpus\QueryBuilder\ExpressionFactory` class.
:::

| Feature | MariaDB | MySQL | PostgreSQL | SQLite | SQL Server | Factory method | Class | Notes |
|:--------|:-------:|:-----:|:----------:|:------:|:----------:|:---------------|:------|:------|
| `ARRAY[<expr> [, ...]]` | Yes | Yes | Yes | Yes | Yes | `array()` | `ArrayValue` | |
| `CASE WHEN <expr> THEN <expr> [...] [ELSE <expr>] END` | Yes | Yes | Yes | Yes | Yes | `caseWhen()` | `CaseWhen` | |
| `CAST(<val> AS <type>` | Yes | Yes | Yes | Yes | Yes | `cast()` | `Cast` | |
| `CONCAT(<string>, ...)` | Dialect | Dialect | Yes | Yes | Dialect | `concat()` | `Concat` | Standard is formatted using the `||` operator. |
| `IF <expr> THEN <expr> ELSE <expr> END` | Yes | Yes | Yes | Yes | Yes |`ifThen()` | `IfThen` | Is always converted using a `CASE WHEN` expression. |
| `VALUES (...) [, ...]` | Warning | Yes | Yes | Yes | Yes | `constantTable()` | `ConstantTable` | |
| `ROW (...)` | Yes | Yes | Yes | Yes | Yes | `row()` | `Row` | |
| `<function>([<expr> [, ...]])` | Yes | Yes | Yes | Yes | Yes | `functionCall()` | `FunctionCall` | |
| `NULL` | Yes | Yes | Yes | Yes | Yes | `null()` | `NullValue` | Also passing PHP `null` as a value will format the `NULL` statement. |
| `HASH(<expr>, <algo>)` | Warning | Warning | Warning | Warning | Warning | `hash()`, `md5()`, `sha1()` | `StringHash` | Only `MD5()` and `SHA1()` for MariaDB and MySQL, PostgreSQL requires the crypto extension to be enabled for other than `MD5()`. |

# Various non-standard SQL expressions

Those expressions were added because commonly used in various applications using
this query builder.

:::warning
Most are non-standard SQL and sometime requires complex functions or
non-efficient generated SQL code in order reach usability.

You can safely use them, but it requires you to understand the generated
code if you need to evaluate their performance impact on your choosen
RDMS and in your own generated queries.
:::


:::info
Factory method in the following table refers to methods of the `MakinaCorpus\QueryBuilder\ExpressionFactory` class.
:::

| Feature | MariaDB | MySQL | PostgreSQL | SQLite | SQL Server | Factory method | Class | Notes |
|:--------|:-------:|:-----:|:----------:|:------:|:----------:|:---------------|:------|:------|
| `MOD (<val>, <val>)` | Dialect | Dialect | Dialect | Dialect | Dialect | `mod()` | `Modulo` | Modulo arithmetic expression. |
| `LPAD(<expr>, <int>, <expr>)` | Dialect | Dialect | Dialect | Downgrade | Downgrade | `lpad()` | `Lpad` | String left pad. |
| `RPAD(<expr>, <int>, <expr>)` | Dialect | Dialect | Dialect | Downgrade | Downgrade | `rpad()` | `Rpad` | String right pad. |
| `RANDOM()` | Dialect | Dialect | Dialect | Warning | Dialect | `random()` | `Random` | Returns an float number between 0 and 1. |
| `RANDOM_INT()` | Downgrade | Downgrade | Downgrade | Downgrade | Downgrade | `randomInt()` | `RandomInt` | Returns a int between given bounds. |

## Comparison expressions

:::info
Factory method in the following table refers to methods of the `MakinaCorpus\QueryBuilder\Where` class.
:::

| Feature | MariaDB | MySQL | PostgreSQL | SQLite | SQL Server | Factory method | Class | Notes |
|:--------|:-------:|:-----:|:----------:|:------:|:----------:|:---------------|:------|:------|
| `EXSITS` | Yes | Yes | Yes | Yes | Yes | `exists()` | `Comparison` | - |
| `NOT EXISTS` | Yes | Yes | Yes | Yes | Yes | `notExists()` | `Comparison` | - |
| `=` | Yes | Yes | Yes | Yes | Yes | `isEqual()` | `Comparison` | - |
| `<>` | Yes | Yes | Yes | Yes | Yes | `isNotEqual()` | `Comparison` | - |
| `LIKE` | Yes | Yes | Yes | Yes | Yes | `isLike()` | `Like` | - |
| `NOT LIKE` | Yes | Yes | Yes | Yes | Yes | `isNotLike()` | `Like` | - |
| `ILIKE` | Yes | Yes | Yes | Yes | Yes | `isLikeInsensitive()` | `Like` | - |
| `NOT ILIKE` | Yes | Yes | Yes | Yes | Yes | `isNotLikeInsensitive()` | `Like` | REGEX itself is subject to dialect peculiarities. |
| `SIMILAR TO` | Yes | Yes | Yes | Yes | Yes | `isSimilarTo()` | `SimilarTo` | REGEX itself is subject to dialect peculiarities. |
| `NOT SIMILAR TO` | Yes | Yes | Yes | Yes | Yes | `isNotSimilarTo()` | `SimilarTo` | - |
| `IN` | Yes | Yes | Yes | Yes | Yes | `isIn()` | `Comparison` | - |
| `NOT IN` | Yes | Yes | Yes | Yes | Yes | `isNotIn()` | `Comparison` | - |
| `>` | Yes | Yes | Yes | Yes | Yes | `isGreater()` | `Comparison` | - |
| `<` | Yes | Yes | Yes | Yes | Yes | `isLess()` | `Comparison` | - |
| `>=` | Yes | Yes | Yes | Yes | Yes | `isGreaterOrEqual()` | `Comparison` | - |
| `<=` | Yes | Yes | Yes | Yes | Yes | `isLessOrEqual()` | `Comparison` | - |
| `BETWEEN` | Yes | Yes | Yes | Yes | Yes | `isBetween()` | `Between` | - |
| `NOT BETWEEN` | Yes | Yes | Yes | Yes | Yes | `isNotBetween()` | `Between` | - |
| `NULL` | Yes | Yes | Yes | Yes | Yes | `isNull()` | `Comparison` | - |
| `NOT NULL` | Yes | Yes | Yes | Yes | Yes | `isNotNull()` | `Comparison` | - |

:::warning
Future work is planned on `ARRAY` and `JSON` operators: they were previously implemented in
`makinacorpus/goat-query` package and will be restored in a near future.
:::

## Schema alteration (experimental)

| Feature | MariaDB | MySQL | PostgreSQL | SQLite | SQL Server | Notes |
|:--------|:-------:|:-----:|:----------:|:------:|:----------:|:------|
| Add column | Yes | Yes | Yes | Yes | Planned | Collations might have problems |
| Drop column | Yes | Yes | Yes | Yes | Planned | - |
| Modify column | Downgrade | Downgrade | Yes | Planned | Planned | Collations might have problems |
| Rename column | Yes | Yes | Yes | No | Planned | - |
| Drop any constraint | Yes | Yes | Yes | No | Planned | - |
| Modify any constraint | No | No | No | No | No | - |
| Rename any constraint | No | No | No | No | No | - |
| Add foreign key | Downgrade | Downgrade | Yes | Planned | Planned | - |
| Modify foreign key | No | No | No | No | No | - |
| Remove foreign key | Yes | Yes | Yes | Planned | Planned | - |
| Rename foreign key | No | No | No | No | No | - |
| Create index | Yes | Yes | Yes | Yes | Planned | - |
| Drop index | Yes | Yes | Yes | Yes | Planned | - |
| Rename index | Yes | Yes | Yes | Yes | Planned | - |
| Add primary key | Yes | Yes | Yes | Planned | Planned | - |
| Drop primary key | Yes | Yes | Yes | Planned | Planned | - |
| Create table | Yes | Yes | Yes | Yes | Planned | - |
| Drop table | Yes | Yes | Yes | Yes | Planned | - |
| Rename table | Yes | Yes | Yes | Planned | Planned | - |
| Add unique key | Yes | Yes | Yes | Yes | Planned | - |
| Drop unique key | Yes | Yes | Yes | Yes | Planned | - |

:::info
MySQL and MariaDB do not support the `DEFERRABLE` constraints. It will simply be
ignored when specified.
:::

:::info
MySQL and MariaDB do not support `NULLS [NOT] DISTINCT` on keys, attempts in
using this feature will raise exceptions.
:::

:::warning
SQLite requires a `DROP` then `CREATE` or `ADD` for most modification or rename
operations. This hasn't be implemented yet.
:::

:::warning
SQLite requires a `DROP` then `CREATE` or `ADD` for most modification or rename
operations. This hasn't be implemented yet.
:::

:::warning
SQL Server is not implemented yet, but is planned.
:::

:::warning
Collation support is unforgiving, it simply passes the collation names to the SQL server.
:::
