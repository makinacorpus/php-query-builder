# Where (predicates)

`WHERE` clauses can be built using the `MakinaCorpus\QueryBuilder\Where` class.

Where class provides comparison expressions building methods documented in this
page.

For all queries that supports it (ie. ``SELECT``, ``UPDATE`` and ``DELETE``)
you can access a where instance directly using the following methods:

 - `where(mixed $column, mixed $vlaue, string $operator)`
 - `whereRaw(mixed $expression, mixed $arguments = null)`

## Method parameters

### Column parameter

For most methods of the `Where` class, first parameter when named `$column` is
opiniated and will always consider you pass a column name if you give a bare
string, hence:

 - any `MakinaCorpus\QueryBuilder\Expression` instance will be kept as-is,
 - `"foo"` will be converted to `new ColumnName('foo')`,
 - `"foo.bar"` will be converted to `new ColumnName('foo', 'bar')`.

### Value parameter

For most methods of the `Where` class, second parameter when named `$value` is
opiniated and will always consider you pass an input value, hence:

 - any `MakinaCorpus\QueryBuilder\Expression` instance will be kept as-is,
 - any other value will be converted to a `MakinaCorpus\QueryBuilder\Expression\Value`
   instance which holds the value, and no type, and value conversion
   responsability will be left to the bridge.

### Working with callbacks

All methods for all parameters accept callbacks. Callback return type will
then be treated as if it was itself the method parameter.

For example:

```php
use MakinaCorpus\QueryBuilder\Where;

assert($where instanceof Where);

$where->compare(
    fn () => 'foo.bar',
    12,
);
```

And:

```php
use MakinaCorpus\QueryBuilder\Where;

assert($where instanceof Where);

$where->compare(
    'foo.bar',
    12,
);
```

Will yield an equivalent result.

### Callback and `raw()` method

When using a callback passed to the `raw()` method, you may omit the callback
return, since this callback will get the `MakinaCorpus\QueryBuilder\Where`
instance as first parameter.

For example:

```php
use MakinaCorpus\QueryBuilder\Where;

assert($where instanceof Where);

$where->raw(
    function (Where $where) {
        $where
            ->isEqual('foo', 12)
            ->isBetween('foo', 7, 11)
    },
);
```

Will yield the following SQL code:

```sql
WHERE
    "foo" = 12
    AND "bar" BETWEEN 7 AND 11
```

If you want a short syntax, you may even use a short arrow function, callback
return will be detected to be the `Where` instance and discarded, and will
work transparently:

```php
use MakinaCorpus\QueryBuilder\Where;

assert($where instanceof Where);

$where->raw(
    fn (Where $where) => where
        ->isEqual('foo', 12)
        ->isBetween('foo', 7, 11),
);
```

This syntax is pure sugar candy for people working with the
query builder who want to write queries without breaking the chain:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder
    ->select('some_table')
    ->column('*')
    ->where(
        fn (Where $where) => $where->isBetween('foo', 1, 10)
    )
    // ...
```

## Where `.. = ..`

The ``comparison($column, $value, $operator)`` method allows you to arbitrarily filter the
SELECT query with any values.

Arguments can be:

 - `$column`: if a string is given

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder
    ->select('some_table', 't')
    ->column('a')
    ->where('b', 12)
;
```

Is equivalent to:

```sql
SELECT "a" FROM "some_table" AS "t" WHERE "b" = 12
```

:::tip
``$value`` parameter can be any PHP type that is supported by the converter, please
:ref:`see the datatype documentation <data-typing>`: **This is always true for every**
**kind of value parameter, in every method**.
:::

Example using the ``$operator`` argument:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder
    ->select('some_table', 't')
    ->column('a')
    ->where('some_date', new \DateTime(), '<')
;
```

Is equivalent to:

```sql
SELECT "a" FROM "some_table" AS "t" WHERE "some_date" < '2018-02-16 15:48:54'
```

:::warning
The ``$operator`` value will be left untouched in the final SQL string, use it
wisely: never allow user arbitrary values to reach this, it is opened to SQL
injection.
:::

Additionnaly, you can use a callback to set the conditions, simply provide any
callable that takes a ``Where`` instance as its first argument, and set your
conditions there:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\Where;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder
    ->select('some_table', 't')
    ->where(function (Where $where) {
        $where
            ->isEqual('some_table.id', 12)
            ->isGreaterOrEqual('some_table.birthdate', new \DateTimeImmutable('2019-09-24'))
            // ...
        ;
    })
;
```

Which is equivalent to:

```sql
SELECT *
FROM "some_table" "t"
WHERE
    "some_table"."id" = 12
    AND "some_table"."birthdate" >= '2019-09-24'
    -- ...
```

:::tip
Calling ``comparison()`` with a single callable argument is strictly equivalent
to calling ``raw()`` with a single callable argument.
:::

:::warning
In order to keep backward compatibility, and because values and expressions
can be raw strings that may conflict with existing PHP function names,
**function names as strings cannot be used as callables**.
:::

## Where `.. [NOT] IN (..)`

``WHERE .. IN (..)`` condition can be written using the ``comparison()`` method:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder
    ->select('some_table', 't')
    ->column('a')
    ->where('b', [1, 2, 3])
;
```

Is equivalent to:

```sql
SELECT "a" FROM "some_table" AS "t" WHERE "b" IN (1, 2, 3)
```

:::tip
You don't have to manually set the ``$operator`` variable to ``IN``, the query
builder will do it for you.
:::

``WHERE .. NOT IN (..)`` condition needs that you set the ``$operator`` parameter:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\Where;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder
    ->select('some_table', 't')
    ->column('a')
    ->where('b', [1, 2, 3], 'NOT IN')
;

// Or

$select = $queryBuilder
    ->select('some_table', 't')
    ->column('a')
    ->where('b', [1, 2, 3], Where::NOT_IN)
;
```

Are both equivalent equivalent to:

```sql
SELECT "a" FROM "some_table" AS "t" WHERE "b" IN (1, 2, 3)
```

:::tip
You don't have to manually set the ``$operator`` variable to ``IN``, the query
builder will do it for you.
:::

:::tip
You can always mix up ``Where::IN`` with ``Where::EQUAL`` and ``Where::NOT_IN``
with ``Where::NOT_EQUAL``, the query builder will dynamically attempt to fix
it depending on the value type.
:::

## Where `.. [NOT] IN (SELECT ..)`

``WHERE .. IN (SELECT ..)`` condition can be written using the ``where()`` method:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

$nestedSelect = $queryBuilder
    ->getQueryBuilder()
    ->select('other_table')
    ->column('foo')
    ->where('type', 'bar')
;

$select = $queryBuilder
    ->select('some_table')
    ->column('a')
    ->where('b', $nestedSelect)
;
```

Is equivalent to:

```sql
SELECT "a"
FROM "some_table"
WHERE "b" IN (
   SELECT "foo"
   FROM "other_table"
   WHERE "type" = 'bar'
)
```

``WHERE .. NOT IN (SELECT ..)`` condition needs that you set the ``$operator`` parameter:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\Where;

assert($queryBuilder instanceof QueryBuilder);

$nestedSelect = $queryBuilder
    ->select('other_table')->column('foo')->where('type', 'bar')
;

// Then

$select = $queryBuilder
    ->select('some_table')
    ->column('a')
     ->where('b', $nestedSelect, 'NOT IN')
;

// Or

$select = $queryBuilder
    ->select('some_table')
    ->column('a')
    ->where('b', $nestedSelect, Where::NOT_IN)
;
```

Are all equivalent to:

```sql
SELECT "a"
FROM "some_table"
WHERE "b" NOT IN (
   SELECT "foo"
   FROM "other_table"
   WHERE "type" = 'bar'
)
```

## Where `.. [NOT] IN (<TABLE EXPRESSION>)`

This will come later once table expression will be implemented.

## Where `<ARBITRARY EXPRESSION>`

Using the ``whereRaw(callable|string|Expression $statement, mixed $arguments = null)``
you can pass any SQL expresion in the ``WHERE`` clause:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder
    ->select('some_table')
    ->column('a')
    ->whereRaw('1')
;
```

:::tip
`$arguments` parameter is always typed mixed, if you don't give an array
of values, it will consider that you give a single value and use your
input as a value.
:::

:::warning
If you need to pass a `null` value, always use an array as such: `[null]`.
:::

Is equivalent to:

```sql
SELECT "a" FROM "some_table" WHERE 1
```

Additionnaly, you can use a callback to set the expression, callback must return
the expression:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder
    ->select('some_table')
    ->column('a')
    ->whereRaw(fn () => '1')
;
```

You may as well return any ``MakinaCorpus\QueryBuilder\Expression`` instance:

```php
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder
    ->select('some_table')
    ->column('a')
    ->whereRaw(fn () => new Raw('1'))
;
```

You can also use the ``MakinaCorpus\QueryBuilder\Where`` instance given as the
first callback argument, which is the main query where, in this case, you don't
need to return a value:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\Where;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder
    ->select('some_table')
    ->column('a')
    ->whereRaw(fn (Where $where)  => $where->raw('1'))
;
```

Are all equivalent to:

```sql
SELECT "a" FROM "some_table" WHERE 1
```

## `OR / AND` with the Where object

For most complex conditions, with ``OR`` and ``AND`` groups, you will need to
fetch the ``MakinaCorpus\QueryBuilder\Where`` component of the query:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder->select('some_table')->column('a');

$where = $select->getWhere();
// ... Work with Where instance.
```

You may now use the ``MakinaCorpus\QueryBuilder\Where`` object to set advanced conditions.
