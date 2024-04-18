# Schema introspection and alteration

## The schema manager

:::warning
This is experimental work-in-progress and API will change.
:::

:::info
All schema alteration can only happen in the database selected when the
session was connected.
:::

The schema manager is an object that allows you to introspect and alter your
database schema.

Once you have a functional database session instance, you may fetch the
*schema manager* instance:

```php
use MakinaCorpus\QueryBuilder\DatabaseSession;

\assert($session instanceof DatabaseSession);

$schemaManager = $session->getSchemaManager();
```

`$schemaManager` will be an instance of `MakinaCorpus\QueryBuilder\Schema\SchemaManager`.

## Schema introspection

The schema manager allows reading the current schema:

 - read table definitions,
 - read table columns data,
 - read table foreign key and reverse foreign key data.

Other implementations will come later.

:::warning
When possible, this API uses the `information_schema` tables. Nevertheless each database vendor
has its own dialect, even when considering the information schema. Keep in mind that each
vendor will apply its own access control over information it gives back when reading those
catalogs. Result may vary depending upon the current user access rights.
:::

First, you may want to list databases:

```php
$databases = $schemaManager->listDatabases();
// $databases is a string array, each value is a database name.
```

Then get a list of schemas:

```php
$schemas = $schemaManager->listSchemas('my_database');
// $schemas is a string array, each value is a schema name.
```

:::info
All schema manager methods take a mandatory `$database` parameter, and an optional
`$schema` parameter: schemas are namespaces inside a database and are not isolated
from each other, you can work with the full database at once.

Default value for `$schema` is always `public`, which is the default name for at
least PostgreSQL when you create a database from scratch.
:::

:::warning
MySQL doesn't support schemas, this parameter will always be ignored when working
with it, and `listSchemas()` will always return a single value which is `public`.
:::

Then fetch table list for a particular database/schema:

```php
foreach ($schemaManager->listTables('my_database', 'my_schema') as $tableName) {
    // $tableName is a string
}
// $databases is a string array, each value is a database name.
```

And now, you may also load table information this way:

```php
$table = $schemaManager->getTable('my_database', 'my_table', 'my_schema');
// $table is now an instance of MakinaCorpus\QueryBuilder\Schema\Table
```

This API is still experimental, and currently works only with MySQL and derivatives
and PostgreSQL. Your IDE and browsing the code will give you all methods that you
should know of easily. More documentation will come later.

## Schema alteration

The schema manager allows data manipulation. For making changes, you must first
start a transaction:

```php
// Parameter is optional, it is the default schema in which to add new elements.
$transaction = $schemaManager->modify('some_schema');
```

:::warning
A single transaction can only work in a single database.
:::

:::info
Vendors that don't support DDL statements in transaction won't have a real
transaction started. For now, only PostgreSQL will benefit from a real database
transaction.
:::

:::tip
Please read the [feature matrix](../introduction/features#schema-alteration-experimental)
for current feature status.
:::

### Basic transaction

Considering the API is fluent and all methods can be chained, here is an example
of a simple table creation:

```php
$schemaManager
    ->modify()
        ->createTable(name: 'user')
            ->column(name: 'id', type: 'serial', nullable: false)
            ->primaryKey(['id'])
            ->column(name: 'enabled', type: 'bool', nullable: false, default: 'false')
            ->column('email', 'text', true)
            ->uniqueKey(columns: ['email'])
            ->index(columns: ['email'])
        ->endTable()
    ->commit()
```

:::warning
Because API may evolve and add new parameters to builder functions, it is recommended
to use argument naming when using the builder.
:::

You may also simply issue direct statements, considering the manipulated table already
exists in your schema:

```php
$schemaManager
    ->modify()
        ->column(table: 'user', name: 'id', type: 'serial', nullable: false)
        ->primaryKey(table: 'user', ['id'])
        ->column(table: 'user', name: 'enabled', type: 'bool', nullable: false, default: 'false')
        ->column(table: 'user', 'email', 'text', true)
        ->uniqueKey(table: 'user', columns: ['email'])
        ->index(table: 'user', columns: ['email'])
    ->commit()
```

:::info
Table creation requires you to use the `createTable()` method nesting.
:::

### Conditions and branches

You may write re-entrant schema alteration procedures by using conditional branches
in the code, consider the previous example:

```php
$schemaManager
    ->modify()
        ->ifTableNotExists(table: 'user')
            ->createTable(name: 'user')
                ->column(name: 'id', type: 'serial', nullable: false)
                ->primaryKey(['id'])
                ->column(name: 'enabled', type: 'bool', nullable: false, default: 'false')
            ->endTable()
        ->endIf()
        ->ifColumnNotExists(table: 'user', column: 'email')
            ->column(table: 'user', 'email', 'text', true)
            ->uniqueKey(table: 'user', columns: ['email'])
            ->index(table: 'user', columns: ['email'])
        ->endIf()
    ->commit()
```

:::info
There is no `else()` statement yet, it might be implemented in in the future.
:::

### Arbitrary SQL queries

There are use cases where you would want to execute custom SQL during a schema
update procedure, for example when populating a column after creation.

Let's consider the following scenario:

 - your `user` table `name` column is required to be lowercased,
 - it must be renamed as `username`.

:::info
This very simple scenario could be done using one single `ALTER TABLE`
SQL query on most vendors, but for the sake of example we are decomposing it.
:::

We may write it this way:

```php
$schemaManager
    ->modify()
        ->ifColumnNotExists('user', 'username')
            ->addColumn('user', 'username', 'varchar(64)', false)
            ->query(
                fn (QueryBuilder $builder) => $builder
                    ->update('user')
                    ->set(
                        'username',
                        $builder->expr()->raw('LOWER(name)')
                    )
                    ->executeStatement()
            )
            ->dropColumn('user', 'name')
        ->endIf()
    ->commit()
```

:::info
Callback first and only argument will always be an instance of
`MakinaCorpus\QueryBuilder\QueryBuilder`. You may or may not use it.
:::

:::info
Callback return will be ignored.
:::

### Using arbitrary SQL as condition

You may want to execute a schema change given some arbitrary conditions,
or if you need an unsupported schema manager operation. In this case,
you may use an arbitrary callback as a condition:

```php
$schemaManager
    ->modify()
        ->ifCallback(
            fn (QueryBuilder $builder) => $builder
                ->select()
                ->columnRaw('true')
                ->executeQuery()
                ->fetchOne()
        )
            ->dropColumn('user', 'name')
        ->endIf()
    ->commit()
```

:::info
Callback first and only argument will always be an instance of
`MakinaCorpus\QueryBuilder\QueryBuilder`. You may or may not use it.
:::

:::info
Callback return will be casted to `bool`.
:::

### Self-documenting example

The `$transaction` object is an instance of `MakinaCorpus\QueryBuilder\Schema\Diff\SchemaTransaction`
implementing the builder pattern, i.e. allowing method chaining.

More detailed documentation will be written later, but here is an example of usage:

```php
$schemaManager
    // Create the transaction object, no transaction will be started at this
    // point, but an in-memory changelog is created for recording all changes
    // you are going to do.
    ->modify()

        // Create a "user" table
        ->createTable(name: 'user')
            // Create an "id" column with the "serial" type.
            // Types are arbitrary, and will be propagated to the database
            // as raw SQL, you can write anything here.
            ->column(
                name: 'id',
                type: 'serial',
                nullable: false,
            )
            // Primary key is not mandatory, and may contain more than one
            // column, in case you'd ask.
            ->primaryKey(['id'])

            // Another column, with a default value. Same as types here,
            // the default value will be propagated as raw SQL, in order to
            // allow you write complex statements, use function calls, etc...
            // This later will change, and give some level of normalization
            // but until this is designed, the API is voluntarily tolerant
            // with your input.
            ->column(
                name: 'enabled',
                type: 'bool',
                nullable: false,
                default: 'false',
            )

            // Now let's add a column with a unique key index over it.
            // Let's make it nullable for fun.
            ->column('email', 'text', true)
            // Pretty much like primary key, multiple columns are allowed.
            ->uniqueKey(['email'])

            // This index probably be created implicitely by your database
            // but let's create one for the sake of example. Multiple columns
            // are allowed too.
            ->index(['email'])

        // Back to the transaction.
        ->endTable()

        ->createTable('user_role')
            ->column('user_id', 'int', false)
            ->column('role', 'text', false)
            ->primaryKey(['user_id', 'role'])

            // And now a foreign key (mutiple columns allowed too):
            ->foreignKey(
                foreignTable: 'user',
                columns: [
                    'user_id' => 'id',
                ],

                // All constraints and indexes can be explicitly named.
                name: 'user_role_user_id_fk',

                // And you may target another schema as well:
                foreignSchema: 'public',

                // "ON DELETE" and "ON UPDATE" behaviors will always be "NO ACTION"
                // per default, in order to avoid accidental data deletion.
                onDelete: ForeignKeyAdd::ON_DELETE_NO_ACTION,
                onUpdate: ForeignKeyAdd::ON_UPDATE_NO_ACTION,

                // And all constraints are deferrable per default.
                deferrable: true,
                initially: ForeignKeyAdd::INITIALLY_DEFERRED,
            )
        ->endTable()

        // All methods exist outside the table builder as well:

        ->addColumn(/* ... */)
        ->dropColumn(/* ... */)
        ->modifyColumn(/* ... */)
        ->renameColumn(/* ... */)

        ->dropConstraint(/* ... */)
        ->modifyConstraint(/* ... */)
        ->renameConstraint(/* ... */)

        ->addForeignKey(/* ... */)
        ->modifyForeignKey(/* ... */)
        ->dropForeignKey(/* ... */)
        ->renameForeignKey(/* ... */)

        ->createIndex(/* ... */)
        ->dropIndex(/* ... */)
        ->renameIndex(/* ... */)

        ->addPrimaryKey(/* ... */)
        ->dropPrimaryKey(/* ... */)

        ->dropTable(/* ... */)
        ->renameTable(/* ... */)

        ->addUniqueKey(/* ... */)
        ->dropUniqueKey(/* ... */)


    // This method call begins the real database transaction, apply each changes
    // you asked for, in the same order you asked, then commit the transaction.
    ->commit()
```

There's much more you can do, but beware that for now, most renaming actions are
not implemented yet, because generally each vendor has its own syntax regarding
those alteration. For now, it requires you to first drop then recreate for most
things (except for columns and tables which can be renamed). You may also drop
anything, methods do exist for this.

:::info
Allmost all code in the `MakinaCorpus\QueryBuilder\Schema\Diff` is mainly composed
of simple DTOs and classes implementing the builder pattern: those classes are
generated.
:::
