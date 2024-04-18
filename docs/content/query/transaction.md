# Transactions

## Introduction

As soon as you have a working `DatabaseSession` instance, you may use transactions.

SQL transaction supports the given level of features:

 - Set or change the transaction isolation level among the 4 SQL level:
   `READ UNCOMMITTED`, `READ COMMITTED`, `REPEATABLE READ` or `SERIALIZABLE` (default is `REPEATABLE READ`).
 - Arbitrary `SAVEPOINT` at any time, `ROLLBACK TO SAVEPOINT`.

## Usage

Consider that you are manipulating a brige instance, which name is `$brige`, you can
simply start a transaction this way:

```php
use MakinaCorpus\QueryBuilder\DatabaseSession;

assert($session instanceof DatabaseSession);

$transaction = $session->beginTransaction();
```

You may also create the stub object for it, then start it later:

```php
use MakinaCorpus\QueryBuilder\DatabaseSession;

assert($session instanceof DatabaseSession);

$transaction = $session->createTransaction();

$transaction->isStarted(); // returns false

$transaction->start();

$transaction->isStarted(); // returns true
```

Then you may continue issuing any SQL query you need to be in the transaction.

Then later commit it:

```php
$transaction->commit();
```

Error handling is up to you, we advice writing such algorightm to do it right:

```sql
use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Error\Server\ServerError;

assert($session instanceof DatabaseSession);

$transaction = null;
try {
    $transaction = $session->beginTransaction();

    // ... you SQL statements here ...

    $transaction->commit();

} catch (ServerError $e) {
    if ($transaction) {
        $transaction->rollback();
    }

    throw $e;
}
```

Transaction `ROLLBACK` is never issued automatically, one exception stands: if
the transaction objet goes out of scope, when the destructor is called, then
`ROLLBACK` is issued.

All transactions must be `COMMIT` explicitely, or will be `ROLLBACK` later.

:::warning
Transaction is shared for the `DatabaseSession` instance which means that if
you start a transaction, it will remain in memory until it is being commited
or rollbacked. Code later in stack will issue SQL queries in the same
transaction until it's finished.
:::

## Savepoints

Once your transaction is started, set a `SAVEPOINT`:

```php
$someSavepoint = $transaction->savepoint('some_name');
```

You can then either commit the savepoint:

```php
// Issues a "COMMIT;"
$someSavepoint->commit();
```

Or rollback to the savepoint:

```php
// Issues a "ROLLBACK TO "some_name";"
$someSavepoint->rollback();
```

:::warning
In all cases, it's your job to handle errors via exception catching.
:::

:::info
You can nest savepoints.
:::

## Known vendors limitations

Transaction support is very uneven among vendors:

 - SQLite cannot specify another isolation level than `SERIALIZABLE`,
   this will always be the default.

 - MySQL and MariaDB cannot change the isolation level in a pending transaction,
   only SQLServer and PostgreSQL support that.

 - SQLite cannot change constraints to `DEFERRED` or `IMMEDIATE` in a pending transaction,
   it can only be set when the transaction begins.

 - MySQL and MariaDB simply don't support changing the `DEFERRED` or `IMMEDIATE` constraints
   state in transactions. They all are immediate, and live with it.
