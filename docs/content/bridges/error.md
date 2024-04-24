# Error handling

## Query builder errors

Query builder errors are the generic exceptions for the whole API which are
not server errors, they are used for:

 - misconfiguration errors,
 - query builder invalid arguments errors,
 - value conversion errors,
 - result set errors.

They all inherit from `MakinaCorpus\QueryBuilder\Error\QueryBuilderError`.

## Server errors

All server side errors are exception, when a driver error is caught and supported,
it will be converted to a `MakinaCorpus\QueryBuilder\Error\Server\*` exception.

When an error is not supported then the generic
`MakinaCorpus\QueryBuilder\Error\Server\ServerError` is raised.

## Bridge errors passthrough

Each bridge implements one or more `MakinaCorpus\QueryBuilder\Bridge\ErrorConverter`
classes, depending upon the underlaying connector and platform.

This object converts the driver error to a uniformized exception provided under
the `MakinaCorpus\QueryBuilder\Error\Bridge` namespace.

Per default, specific error converters are lazily registered when a first error
happens. When used with Doctrine DBAL, you might want to keep a seemless integration
with it, and let the `doctrine/dbal` exception pass, in order to do this, simply
call the `Bridge::disableErrorConverter()` method when initializing the bridge:

```php
use Doctrine\DBAL\DriverManager;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineBridge;

$connection = DriverManager::getConnection(['driver' => 'pdo_pgsql', /* ... */]);
$bridge = new DoctrineBridge($connection);

// Here it is:
$bridge->disableErrorConverter();
```

:::info
This works with all bridges.
:::

:::info
The `makinacorpus/query-builder-bundle` will per default configure the `doctrine/dbal`
bridge to disable specific error handling in order to be completely transparent for
users that will usually expect Doctrine DBAL errors.
:::

:::warning
This method must be called before any SQL request really happens.
:::
