---
hideExcerpt: true
---

# Features matrix

The exposed feature matrix is incomplete and grows over time.

In next tables, you will see the following legend:

 - *Standard* means the feature is supported, and has no specific dialect.
 - *Warning*  means the feature is supported as standard, but some advanced forms may raise error on the server side.
 - *Dialect* means there is a specific dialect implemented.
 - *Downgrade* when a less efficient with feature parity variant is used because the platform does not implement it.
 - *Planned* when a feature is known to work but not yet implemented or tested.
 - *No* Means not implemented, sometime implemented but not officially supported.

## Basic SQL syntax

| Feature | MariaDB | MySQL | PostgreSQL | SQLite | SQL Server | Notes |
|:--------|:-------:|:-----:|:----------:|:------:|:----------:|:------|
| `AGGREGATE(...)` | Standard | Standard | Standard | Standard | Standard | Standard |
| `AGGREGATE() FILTER(...)` | Downgrade | Downgrade | Standard | No | Downgrade | For all other than PostgreSQL, a fallback that uses `CASE ... END` replaces the `FILTER` clause. |
| `AGGREGATE() OVER(...)` | Standard | Standard | Standard | Standard | Standard | In some cases, `PARITION BY 1` is automatically added when `ORDER BY` is present. |
| `DELETE FROM ... [JOIN] ...` | Dialect | Dialect | Standard | Standard | Standard | First `JOIN` is automatically converted to a cross join in `FROM` for most implementations. |
| `DELETE` | Standard | Standard | Standard | Standard | Standard | - |
| `INSERT IGNORE` | Downgrade | Downgrade | Dialect | Not implemented | Not implemented | MariaDB and MySQL can't target a particular constraint violation. |
| `INSERT SELECT` | Standard | Standard | Standard | Standard | Standard | Standard |
| `INSERT UPDATE` | Downgrade | Downgrade | Dialect | Not implemented | Not implemented | MariaDB and MySQL can't target a particular constraint violation. |
| `INSERT VALUES` | Standard | Standard | Standard | Standard | Standard | Standard |
| `JOIN` | Standard | Standard | Standard | Standard | Standard | - |
| `MERGE` | No | No | Planned | No | Planned | - |
| `RETURNING\|OUTPUT` | No | No | Dialect | No | Dialect | SQL Server differenciate rows before and after mutation, only rows after mutation are handled. |
| `SELECT DISTINCT` | Standard | Standard | Standard | Standard | Standard | - |
| `SELECT WINDOW AS (...)` | No | No | Standard | No | No | Only PostgreSQL seems to support it, yet we generate it for all. |
| `SELECT` | Standard | Standard | Standard | Standard | Standard | - |
| `UPDATE FROM ... [JOIN] ...` | Dialect | Dialect | Standard | Standard | Standard | First `JOIN` is automatically converted to a cross join in `FROM` for most implementations. |
| `UPDATE` | Standard | Standard | Standard | Standard | Standard | - |
| `VALUES (...), [...]` | Warning | Standard | Standard | Standard | Standard | MariaDB doesn't support `VALUES` aliasing, using it will raise server errors. |
| `WITH` | Standard | Standard | Standard | Standard | Standard | There is no dialect syntactic differences. |
| `WITH RECURSIVE` | Planned | Planned | Planned | Planned | Planned | - |

## Various SQL expressions

| Feature | MariaDB | MySQL | PostgreSQL | SQLite | SQL Server | Factory method | Class | Notes |
|:--------|:-------:|:-----:|:----------:|:------:|:----------:|:---------------|:------|:------|
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |

# Various non-standard SQL expressions

| Feature | MariaDB | MySQL | PostgreSQL | SQLite | SQL Server | Factory method | Class | Notes |
|:--------|:-------:|:-----:|:----------:|:------:|:----------:|:---------------|:------|:------|
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |


## Comparison expressions

| Feature | MariaDB | MySQL | PostgreSQL | SQLite | SQL Server | Factory method | Class | Notes |
|:--------|:-------:|:-----:|:----------:|:------:|:----------:|:---------------|:------|:------|
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
| `SELECT` | Ma | My | Po | Sl | Ms | `FOO()` | `FOO` | |
