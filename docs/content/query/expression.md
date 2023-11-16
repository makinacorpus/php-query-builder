# Working with expression

## Introduction

This API defines numerous SQL expression as classes that you can inject at
various (if not all) places of the builder.

Each expression is in PHP a stateless class you can instanciate with values
which will result in the writer converting it into nice SQL code.

There are four different ways to instanciate expressions, depending upon
the context you are in, which allows you to easily create them.

The choice of the method is up to you, there is no better way, all methods
are and will remain supported for the current major version lifetime.

## Implicit creation

Anywhere the API allows you to pass arbitrary values, expressions will
be implicitely created for the writer to be able to write SQL.

In all cases, whenever this is the case, each builder method documents
which expression will be created, for example:

```php
use MakinaCorpus\QueryBuilder\Query\Select;

// Will implicitely create a TableName('my_table') instance.
$select = new Select('my_table');

// Will implicitely create a ColumnName('my_column', 'my_table') instance.
$select->column('my_table.my_column');

// Will implicitely create Raw('count(foo) = 2') instance, and inject
// it into the Where instance.
// It also creates an ColumName('some_other_column') instance.
$select->havingRaw('count(?::column) = 2', ['some_other_column']);
```

This is true for every method of the builder.

::: tip
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
use MakinaCorpus\QueryBuilder\Query\Select;

$select = new Select(
    new TableName('my_table'),
);

$select->column(
    new ColumnName('my_column', 'my_table'),
);

$select->having(
    new Raw(
        'count(?) = 2',
        [
            new ColumnName('some_other_column'),
        ],
    )
);
```

::: tip
Using this syntax can lead into having a lot of `use` statements, that you
probably may not want to have. It also makes the code harder to read in most
cases.
:::

## Using the ExpressionFactory

There are some cases where you probably want to explicitely instanciate
expressions, because you either hit a query builder limitation and want to
work around it, or because you simply want to write it by yourself.

For those use case, you can use the `ExpressionFactory` shortcut. Let's
once again convert the previous code:

```php
use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\Query\Select;

$select = new Select(
    ExpressionFactory::table('my_table'),
);

$select->column(
    ExpressionFactory::column('my_table.my_column'),
);

$select->having(
    ExpressionFactory::raw(
        'count(?) = 2',
        [
            ExpressionFactory::column('some_other_column'),
        ],
    )
);
```

::: tip
Using `ExpressionFactory` will give you a complete, self-documenting and
comprehensive list of supported expressions if your editor supports
automatic completion.
:::

## Using a query ExpressionFactory

This method is equivalent from using the `ExpressionFactory` except that
you can fetch an instance of the factory directly from all query objects.

Once again, same code:

```php
use MakinaCorpus\QueryBuilder\Query\Select;

$select = new Select('my_table');

// This actually simply return `new ExpressionFactory()` without further ado.
// ExpressionFactory methods are static, but PHP allows static methods to be
// called dynamically, it is semantically correct and will work transparently.
$expr = $select->expression();

$select->column(
    $expr->column('my_table.my_column'),
);

$select->having(
    $expr->raw(
        'count(?) = 2',
        [
            $expr->column('some_other_column'),
        ],
    )
);
```
