# Working with expression

## Introduction

This API defines numerous SQL expression as classes that you can inject at
various (if not all) places of the builder.

Each expression is a PHP class you can instanciate with values
which will result in the writer converting it into SQL code compatible with
the configured dialect.

There are four different ways to instanciate expressions, depending upon
the context you are in, which allows you to easily create them.

**The choice of the method is up to you, there is no best way**, all methods
are and will remain supported for the current major version lifetime.

:::tip
**The query builder never write SQL code, solely the writer does**: this
avoids coupling of the writer inside the query builder, and makes the
expression tree dialect-free, and more portable.
:::

## Implicit creation

Anywhere the API allows you to pass arbitrary values, expressions will
be implicitely created for the writer to be able to write SQL.

In all cases, whenever this is the case, each builder method documents
which expression will be created, for example:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder
    // Will implicitely create a TableName('my_table') instance.
    ->select('my_table');

    // Will implicitely create a ColumnName('my_column', 'my_table') instance.
    ->column('my_table.my_column');

    // Will implicitely create Raw('count(?) = 2') instance,
    // and inject it into the Where instance.
    // It also creates an ColumName('some_other_column') instance.
    ->havingRaw('count(?::column) = 2', ['some_other_column'])
;
```

This is true for every method of the builder.

:::tip
The primary goal of this API is to easier SQL code writing for you, the more
expressions are created implicitely, the easier it will be for you to use
it.
:::

## Instanciating an expression

Most effective way of creating an expression is instanciating it directly.

Let's convert the previous code:

```php
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder
    ->select(
        new TableName('my_table'),
    )
    ->column(
        new ColumnName('my_column', 'my_table'),
    )
    ->having(
        new Raw(
            'count(?) = 2',
            [
                new ColumnName('some_other_column'),
            ],
        )
    )
;
```

:::tip
Code is more explicit, it can be easier to read for some, harder for others.
But in all cases, it's the most verbose syntax, which leads into having a lot
of `use` statements. The choice is up you to chose either convention.
:::

:::warning
This method is the one that will create the biggest dependency of your code
on the query builder, and may be the hardest one to migrate in a future next
major version in case of backward compatibility break.
:::

## Using the ExpressionFactory

There are some cases where you probably want to explicitely instanciate
expressions, because you either hit a query builder limitation and want to
work around it, or because you simply want to write it by yourself.

For those use case, you can use the `ExpressionFactory` shortcut. Let's
once again convert the previous code:

```php
use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

$select = $queryBuilder
    ->select(
        ExpressionFactory::table('my_table'),
    )
    ->column(
        ExpressionFactory::column('my_table.my_column'),
    )
    ->having(
        ExpressionFactory::raw(
            'count(?) = 2',
            [
                ExpressionFactory::column('some_other_column'),
            ],
        )
    )
;
```

:::tip
Using `ExpressionFactory` will give you a complete, self-documenting and
comprehensive list of supported expressions if your editor supports
automatic completion.
:::

## Using a query ExpressionFactory

This method is equivalent from using the `ExpressionFactory` except that
you can fetch an instance of the factory directly from all query objects.

Once again, same code:

```php
use MakinaCorpus\QueryBuilder\QueryBuilder;

assert($queryBuilder instanceof QueryBuilder);

// Get the factory.
$expr = $queryBuilder->expression();

// For the sake of example, use implicit name here.
$select = $queryBuilder
    ->select('my_table')
    ->column(
        $expr->column('my_table.my_column'),
    )
    ->having(
        $expr->raw(
            'count(?) = 2',
            [
                $expr->column('some_other_column'),
            ],
        )
    )
;
```

:::tip
`Query::expression()` method simply return `new ExpressionFactory()`.

`ExpressionFactory` methods are static, PHP allows static methods to be
called dynamically, it is semantically correct and will work transparently.
:::
