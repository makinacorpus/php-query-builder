# Value converter

## Input value converter

### Definition

**Input value converter** converts user input values **from PHP to SQL**.

It is plugged per default in the `Bridge` instance, hence for the final user
the `QueryBuilder` and `DatabaseSession` instances, then propagated to the
`ArgumentBag` instance prior query execution.

When `ArgumentBag::getAll()` is called, all values are lazily converted from
PHP to SQL using the types that were specified during query building.

When types are not specified by the user during query building, types are
automatically guessed in a best-effort basis to choose which converter instance
to use in order to generate the SQL value.

Converter plugins are pluggable, you may add your own input converters.

### Input type matrix

:::warning
@todo This section of the documentation is incomplete.

See the [legacy data type matrix](../query/datatype) until the documentation is completed.
:::

## Output value converter

### Definition

**Output value converter** converts user input values **from SQL to PHP**.

It is plugged per default in the `Bridge` instance, hence for the final user
the `QueryBuilder` and `DatabaseSession` instances, then propagated to the
`ResultRow` instance after query execution.

When you decide to iterate over  `ResultRow` instances, by calling
`Result::fetchRow()` then the output converter may be used if you specify
an expected PHP type on the `ResultRow::get()` method. The output converters
are called at this exact moment.

Converter plugins are pluggable, you may add your own output converters.

### Output type matrix

:::warning
@todo This section of the documentation is incomplete.

See the [legacy data type matrix](../query/datatype) until the documentation is completed.
:::

## Registering custom plugins

### Write your implementation

For an input converter, implement the `MakinaCorpus\QueryBuilder\Converter\InputConverter`
interface, interface signature should be enough for you to know what code
you should write.

For an output converter, implement the `MakinaCorpus\QueryBuilder\Converter\OutputConverter`
interface, interface signature should be enough for you to know what code
you should write.

:::tip
Interfaces are designed to allow you implementing both on a same
class, if you feel like input and output code belong to the same place.
:::

### When using a standalone setup

During the query builder setup, you should add the following step,
considering that you followed the [getting started procedure](../introduction/getting-started):

```php
use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Converter\ConverterPluginRegistry;
use MakinaCorpus\QueryBuilder\DefaultQueryBuidler;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\PostgreSQLWriter;

// Here is the code you need to add.
$converterPluginRegistry = new ConverterPluginRegistry();
$converterPluginRegistry->register(
    new MyCustomOutputConverter(),
);
$converter = new Converter();
$converter->setConverterPluginRegistry($converterPluginRegistry);

$escaper = new StandardEscaper();
// Pass here the newly created converter.
$writer = new PostgreSQLWriter($escaper, $converter);

$session = new DefaultQueryBuilder();
```

### When using a bridge

When you setup a bridge, the writer instance will be created automatically
by the bridge, hence the different procedure.

```php
use MakinaCorpus\QueryBuilder\Bridge\AbstractBridge;

assert($session instanceof AbstractBridge);

// Directly register your implementation into the bridge.
$session
    ->getConverterPluginRegistry
    ->register(
        new MyCustomOutputConverter(),
    )
;
```

### When using the Symfony bundle

Implement either `MakinaCorpus\QueryBuilder\Converter\InputConverter` or
`MakinaCorpus\QueryBuilder\Converter\OutputConverter`, register your classes as
services into the container.

:::tip
If your services are not found automatically by autowiring, add the
`query_builder.converter_plugin` tag on each of them.
:::

