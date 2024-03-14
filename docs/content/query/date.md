# Dates and intervals

## Default value conversion

Per default, any `\DateTime` or `\DateTimeImmutable` PHP objects given as values
will be converted to `timestamp` (also known as `datetime` in some vendors) SQL
values (with time zone transparently handled if significant):

```php
$expr = $queryBuilder->expression();

$queryBuilder
    ->select()
    ->column(
        $expr->value(new \DateTimeImmutable())
    )
    ->executeQuery()
    ->fetchOne()
;

// Or

$queryBuilder
    ->raw('select ?', [new \DateTimeImmutable()])
    ->executeQuery()
    ->fetchOne()
;
```

Will both return a value such as: `2024-03-14 13:44:32.456298+00` (precision and offset
depends upon the database vendor).

## Current timestamp

The `current_timestamp` SQL expression may vary depending upon database vendor,
a specific expression is available for portability:

```php
$expr = $queryBuilder->expression();

$queryBuilder
    ->select()
    ->column(
        $expr->currentTimestamp()
    )
    ->executeQuery()
    ->fetchOne()
;
```

Which should return a value such as: `2024-03-14 13:44:32.456298+00` (precision and offset
depends upon the database vendor).

:::tip
You may choose to create a `MakinaCorpus\QueryBuilder\Expression\CurrentTimestamp`
instance manually instead, without using the factory.
:::

## Casting to date

If you require an SQL `date` value instead of a `timestamp`, you can cast your
values:

```php
$expr = $queryBuilder->expression();

$queryBuilder
    ->select()
    ->column(
        $expr->cast(
            new \DateTimeImmutable(),
            'date'
        )
    )
    ->executeQuery()
    ->fetchOne()
;

// Or

$queryBuilder
    ->raw('select cast(? as date)', [new \DateTimeImmutable()])
    ->executeQuery()
    ->fetchOne()
;
```

Will both return a value such as: `2024-03-14`.

## Interval expressions

The SQL `interval` type is only supported by PostgreSQL at the time being,
for all other vendors, intervals are materialized via function parameters or
using a custom dialect instead.

:::warning
You can create `MakinaCorpus\QueryBuilder\Expression\DateInterval` or
`MakinaCorpus\QueryBuilder\Expression\DateIntervalUnit` expressions,
but don't attempt to format those arbitrarily outside of date operator
or function, it won't work and write invalid SQL most of the cases.

Do not manually use interval related expressions outside of data
operators and functions.
:::


## Interval values

:::tip
In opposition to the previous statement, PostgreSQL is the only
database vendor known to handle interval as a type and able to use
interval values outside of date functions.
:::

This mean you can use the `interval` type as a column type, or in
value type casts when using PostgreSQL.

You can type values passed to the query builder using the `interval`
type which will convert the values to an ISO interval string, for
example, all of the following queries being semantically equivalent:

```php
$expr = $queryBuilder->expression();

$interval = \DateInterval::createFromDateString('1 hour 2 minutes');

$queryBuilder->raw('select ?', [$interval]);
$queryBuilder->raw('select interval ?', ['1 hour 2 minutes']);
$queryBuilder->raw('select cast(? as interval)', ['1 hour 2 minutes']);
$queryBuilder->raw('select interval ?', [$interval]);
$queryBuilder->raw('select ?', [$expr->cast($interval, 'interval')]);
$queryBuilder->raw('select ?', [$expr->value($interval, 'interval')]);
```

## Date add, date sub

You may use the `MakinaCorpus\QueryBuilder\Expression\DateAdd` and
`MakinaCorpus\QueryBuilder\Expression\DateSub` to add or substract
interval to dates.

All the following examples are semantically equivalent:

```php
$queryBuilder
    ->select()
    ->column(
        $expr->dateAdd(
            $expr->currentTimestamp(),
            '1 hour 2 minutes',
        )
    )
;

// Or

$queryBuilder
    ->select()
    ->column(
        $expr->dateAdd(
            $expr->currentTimestamp(),
            'PT1H2M',
        )
    )
;

// Or

$queryBuilder
    ->select()
    ->column(
        $expr->dateAdd(
            $expr->currentTimestamp(),
            [
                'hour' => 1,
                'minute' => 2,
            ],
        )
    )
;

// Or

$queryBuilder
    ->select()
    ->column(
        $expr->dateAdd(
            $expr->currentTimestamp(),
            new \DateInterval('PT1H2M'),
        )
    )
;
```

Date substraction has the exact same signature.

You may also provide interval value using an arbitrary expression,
which must return an integer typed value, for example:

```php
$queryBuilder
    ->select()
    ->column(
        $expr->dateAdd(
            $expr->currentTimestamp(),
            $expr->intervalUnit(
                $expr->raw('(select ?)', [12]),
                'hour'
            ),
        )
    )
;
```

## Hydrating SQL dates to PHP

Simply use the row instance instead of using a raw result:

```php
$value = $queryBuilder
    ->raw('select cast(? as date)', [new \DateTimeImmutable()])
    ->executeQuery()
    ->fetchRow()
    ->get(0, \DateTimeImmutable::class)
;
```

The returned `$value` will be an instance `\DateTimeImmutable`.

:::info
Time zone will be handled gracefully, since when using a timestamp with time zone,
most vendors will simply store the UTC value, the hydrator will behave accordingly,
set the UTC time zone at object creation, then convert it to the current default
PHP time zone.
:::

:::tip
You can also hydrate `\DateTime` instances. If you choose `\DateTimeInterface`
instead, a `\DateTimeImmutable` will be hydrated.
:::
