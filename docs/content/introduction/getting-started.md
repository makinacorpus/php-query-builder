---
outline: false
aside: false
layout: doc
---

# Getting Started

All you need to know to start using *Query Builder*.

## Standalone setup

### 1. Install

Install it using composer:

```sh
composer require makinacorpus/query-builder
```

First of all, you need to chose your SQL dialect, this package provides
a few implementations:

 - **MariaDB** >= 10.0,
 - **MySQL** 5.7 and >= 8.0,
 - **PostgreSQL** >= 10,
 - **SQL Server** >= 2019 (previous versions from 2015 are untested but should work),
 - **SQLite** >= 3.0 (previous versions are untested but should work).

### 2. Setup the query builder

```php
use MakinaCorpus\QueryBuilder\DefaultQueryBuidler;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Platform\Writer\PostgreSQLWriter;

// Escaper is the component that ties the query-builder with third-party
// Database Access Layers, such as `doctrine/dbal` or simply `PDO`.
$escaper = new StandardEscaper();

// Writer is the component that writes the SQL code which will take care
// of supported RDMS dialects.
$writer = new PostgreSQLWriter($escaper);

/*
 * User facade for you to build SQL queries.
 */
$queryBuilder = new DefaultQueryBuilder();
```

### 3. Write your query

Let's write a simple query:

```php
use MakinaCorpus\QueryBuilder\DefaultQueryBuidler;

$queryBuilder = new DefaultQueryBuilder();

$query = $queryBuilder
    ->select('users')
    ->column('*')
    ->where('id', 'john.doe@example.com')
;
```

Now you can use your favorite database access layer and execute it.

### 4. Generate the SQL and execute it

Considering you have any database access layer with the following method:

```php
interface MyDbal
{
    public function execute(string $sql, array $parameters): MyDbalStatement;
}
```

Then simply generate SQL and pass it along:

```php
use MakinaCorpus\QueryBuilder\QueryBuidler;

$prepared = $writer->prepare($query);

$myDbal->execute(
    $prepared->toString(),
    $prepared->getArguments()->getAll(),
);
```

You may now proceed with [usage examples](./usage).

:::tip
Raw PHP values that were passed as arguments in your queries will automatically
run throught the converter and be converted to values that the SQL server
understands.

See [the value converter documentation](../converter/converter) for supported data types conversions.
:::

## Doctrine DBAL setup

### 1. Install

Install it using composer:

```sh
composer require makinacorpus/query-builder doctrine/dbal:'^3.7|^4.0'
```

### 2. Setup the query builder

Setting it up is easier than standalone setup:

```php
use Doctrine\DBAL\DriverManager;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineBridge;
use MakinaCorpus\QueryBuilder\DatabaseSession;

// Create or fetch your doctrine/dbal connection.
$connection = DriverManager::getConnection([
    'driver' => 'pdo_pgsql',
    // ... driver options.
]);

// Create the query builder.
$session = new DoctrineBridge($connection);
\assert($session instanceof DatabaseSession);
```

:::warning
For the final user, the *bridge* should be hidden and the
`MakinaCorpus\QueryBuilder\DatabaseSession` exposed instead. The bridge is an
internal component that ties the query builder with a third-party driver.

All useful features are exposed via the `DatabaseSession` whereas the bridge
should remain hidden, as its signature is subject to changes.
:::

:::tip
You don't need to specify the SQL dialect to use, it will be derived from
the `doctrine/dbal` connection automatically, without requiring any extra SQL
query to do so.
:::

### 3. Write your query and execute it

Now we can write a query and execute it directly:

```php
$result = $session
    ->select('users')
    ->column('*')
    ->where('id', 'john.doe@example.com')
    ->executeQuery()
;
```

You may now proceed with [usage examples](./usage).

:::tip
`$result` is an instance of `MakinaCorpus\QueryBuilder\Result\Result`.

See [result documentation](../query/result) for more information.
:::

:::tip
Raw PHP values that were passed as arguments in your queries will automatically
run throught the converter and be converted to values that the SQL server
understands prior being sent as arguments to `doctrine/dbal`.

See [the value converter documentation](../converter/converter) for supported data types conversions.
:::

## PDO setup

### 1. Install

Install it using composer:

```sh
composer require makinacorpus/query-builder
```

### 2. Setup the query builder

Setting it up is easier than standalone setup:

```php
use MakinaCorpus\QueryBuilder\Bridge\Pdo\PdoBridge;
use MakinaCorpus\QueryBuilder\DatabaseSession;

// Create or fetch your PDO connection.
$connection = new \PDO('pgsql:...');

// User facade for you to build SQL queries.
$session = new PdoBridge($connection);
\assert($session instanceof DatabaseSession);
```

:::warning
For the final user, the *bridge* should be hidden and the
`MakinaCorpus\QueryBuilder\DatabaseSession` exposed instead. The bridge is an
internal component that ties the query builder with a third-party driver.

All useful features are exposed via the `DatabaseSession` whereas the bridge
should remain hidden, as its signature is subject to changes.
:::

:::tip
You don't need to specify the SQL dialect to use, it will be derived from
the `PDO` connection automatically.
:::

### 3. Write your query and execute it

```php
$result = $session
    ->select('users')
    ->column('*')
    ->where('id', 'john.doe@example.com')
    ->executeQuery()
;
```

You may now proceed with [usage examples](./usage).

:::tip
`$result` is an instance of `MakinaCorpus\QueryBuilder\Result\Result`.

See [result documentation](../query/result) for more information.
:::

:::tip
Raw PHP values that were passed as arguments in your queries will automatically
run throught the converter and be converted to values that the SQL server
understands prior being sent as arguments to `PDOStatement`.

See [the value converter documentation](../converter/converter) for supported data types conversions.
:::

## Symfony setup

### 1. Install and register bundle

:::warning
For this to work, you need to setup a `doctrine/dbal` connection using the
`doctrine/doctrine-bundle` integration first.
:::


Install the composer dependency as such:

```sh
composer require makinacorpus/query-builder-bundle
```

If Symfony Flex didn't find the bundle (it should have), then register it in
your `config/bundles.php` file as such:

```php
<?php

return [
    // ... Your other bundles.
    MakinaCorpus\QueryBuilderBundle\QueryBuilderBundle::class => ['all' => true],
];
```

### 2. Inject it into a component and use it

Here is a controller action example:

```php
<?php

declare (strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestingController extends AbstractController
{
    #[Route('/testing/query-builder', name: 'testing_query_builder')]
    public function testQueryBuilder(
        DatabaseSession $session,
    ): Response {
        $result = $session
            ->select('some_table')
            ->executeQuery()
        ;

        $data = [];
        foreach ($result->iterateAssociative() as $row) {
            $data[] = $row;
        }

        return $this->json($data);
    }
}
```

You may now proceed with [usage examples](./usage).

:::warning
This will inject the query builder instance plugged on the `default` connection.

If you need to use another connection, please read the next chapter.
:::

### Using a query builder for another connection

You may have configured more than one `doctrine/dbal` connection, this bundle
will register as many `MakinaCorpus\QueryBuilder\DatabaseSession` services as
doctrine connections being configured.

Each service identifier is `query_builder.session.CONNECTION_NAME` where
`CONNECTION_NAME` is the Doctrine bundle configured connection identifier.

:::tip
`query_builder.doctrine.default` is registered as well if you need to deambiguate
or inject it explicitely.
:::
