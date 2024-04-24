# Result

:::tip
This page documents `MakinaCorpus\QueryBuilder\Result\Result` usage
over examples using `doctrine/dbal`, nevertheless everything exposed here
is applicable to the `PDO` and other bridges as well.
:::

## Introduction

This API is mostly a driver-agnostic SQL query builder, yet it provides its own
query result interface in order for bridges to share the same signature,
which is `MakinaCorpus\QueryBuilder\Result\Result`.

In order to keep up with the PHP community, it has been chosen to duplicate
an API-compatible with `Doctrine\DBAL\Result` class signature.

## How to fetch a result

As soon as you use a bridge to re-use a database access layer connection,
you can call `Query::executeQuery()` over your queries, for example:

```php
use Doctrine\DBAL\DriverManager;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineBridge;

$session = new DoctrineBridge(
    DriverManager::getConnection([
        'driver' => 'pdo_pgsql',
        // ... driver options.
    ])
);

$result = $session
    ->select('some_table')
    ->column('*')
    ->executeQuery()
;

foreach ($result->iterateAssociative() as $row) {
    // ...
}
```

You may also directly execute raw SQL code as such:

```php
use Doctrine\DBAL\DriverManager;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineBridge;

$session = new DoctrineBridge(
    DriverManager::getConnection([
        'driver' => 'pdo_pgsql',
        // ... driver options.
    ])
);

$result = $session
    ->executeQuery(
        <<<SQL
        SELECT * FROM "some_table"
        SQL
    )
;

foreach ($result->iterateAssociative() as $row) {
    // ...
}
```

## The ResultRow interface

Per default in opposition with the `Doctrine\DBAL\Result` class,
`MakinaCorpus\QueryBuilder\Result\Result` interface is an iterator, which
means you can directly iterate over.

Iterating over the result directly will give you `MakinaCorpus\QueryBuilder\Result\ResultRow`
instances:

```php
use Doctrine\DBAL\DriverManager;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineBridge;
use MakinaCorpus\QueryBuilder\Result\ResultRow;

$session = new DoctrineBridge(
    DriverManager::getConnection([
        'driver' => 'pdo_pgsql',
        // ... driver options.
    ])
);

$result = $session
    ->executeQuery(
        <<<SQL
        SELECT * FROM "some_table"
        SQL
    )
;

foreach ($result as $row) {
    assert($result instanceof ResultRow);
    // ...
}
```

This interface brings the result set a step higher than using PHP `array`, and give
you a method that allows you to fetch values one by one, adding a PHP type hint for
SQL to PHP value conversion:

```php
foreach ($result as $row) {
    assert($result instanceof ResultRow);

    $intValue = $result->get('some_column', 'int');
    assert(\is_int($intValue) || null === $intValue);

    $dateValue = $result->get('some_other_column', \DateTimeImmutable::class);
    assert($dateValue instanceof \DateTimeImmutable || null === $dateValue);
}
```

:::warning
SQL to PHP value conversion is not implemented yet, and will be soon.

Until it is fully implemented, specifying a PHP type will raise an
exception.
:::

## Result hydrator

Result interface allows you to directly add an hydrator which will be applied
over each row returned, when you iterate on the `Result` instance or when
you call the `Result::fetchHydrated()` method.

This hydrator has the following two possible signatures:

  - `function (ResultRow $row): mixed`
  - `function (array $row): mixed`

Given callback will be instrospected in order to guess which value type needs
to be passed while iterating.

```php

$result = $session
    ->executeQuery(
        <<<SQL
        SELECT "id", "name", "email", "created_at" FROM "users"
        SQL
    )
    ->setHydrator(
        fn (ResultRow $row) => new User(
            id: $row->get('id', UuidInterface::class),
            name: $row->get('name', 'string'),
            email: $row->get('email', 'string'),
            createdAt: $row->get('created_at', \DateTimeImmutable::class),
        )
    )
;

while ($user = $result->fetchHydrated()) {
    assert($result instanceof User);
}

foreach ($result as $row) {
    assert($result instanceof User);
}
```

## Differences with doctrine/dbal Result signature

`Result` methods originating from the `Doctrine\DBAL\Result` class are API
compatible: only a few optional parameters are added:

 - `fetchOne(int|string $valueColumn = 1): mixed` where the `$valueColumn` allows
   you to identify the column to use, instead of simply picking the first.

 - `fetchAllKeyValue(int|string $keyColumn = 0, int|string $valueColumn = 1): array`
   where the `$keyColumn` allows you to change the column used for keys, and `$valueColumn`
   the one used for values.

 - `function fetchFirstColumn(int|string $valueColumn = 0): array` where `$valueColumn`
   allows you to change which column's values is returned.

 - `fetchAllAssociativeIndexed(int|string $keyColumn = 0)`, wheere `$keyColumn` allows
   you to change the column used for keys.

The same signature and behavior applies for all `iterateX()` methods.

Omiting those extra parameters will fallback on Doctrine default behavior.

# Identifying columns

The `Result` and `ResultRow` interfaces allow you to target column names in
various function calls, a column identifier can always be:

 - an `int`, case in which the value will be the Nth value, counting
   columns starts at 0,

 - a `string` case in which the value will be the one named as such.

:::warning
In all cases, when attempting to identify a column with an integer out
of bounds, or using a name that doesn't exist in select will raise an
exception.
:::
