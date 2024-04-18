<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema;

use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Error\Server\TableDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\UnsupportedFeatureError;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Expression\DataType;
use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\Schema\Diff\Browser\ChangeLogBrowser;
use MakinaCorpus\QueryBuilder\Schema\Diff\Browser\SchemaWriterLogVisitor;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\AbstractChange;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\CallbackChange;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnModify;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ConstraintDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ConstraintModify;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ConstraintRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyModify;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ForeignKeyRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexCreate;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\IndexRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\PrimaryKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\PrimaryKeyDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableCreate;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueKeyAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueKeyDrop;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\AbstractCondition;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\CallbackCondition;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\ColumnExists;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\IndexExists;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\TableExists;
use MakinaCorpus\QueryBuilder\Schema\Diff\SchemaTransaction;
use MakinaCorpus\QueryBuilder\Schema\Read\Column;
use MakinaCorpus\QueryBuilder\Schema\Read\ForeignKey;
use MakinaCorpus\QueryBuilder\Schema\Read\Index;
use MakinaCorpus\QueryBuilder\Schema\Read\Key;
use MakinaCorpus\QueryBuilder\Schema\Read\Table;
use MakinaCorpus\QueryBuilder\Type\Type;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

// @todo Because IDE bug, sorry.
\class_exists(Column::class);
\class_exists(ForeignKey::class);
\class_exists(Index::class);

/**
 * Most SQL here tries to be the closest possible to SQL standard.
 *
 * Sadly when it comes to schema introspection, SCHEMATA standard deviates
 * for every vendor, so there is no real standard here, implementations will
 * use it for reading because it will at least be resilient against different
 * versions of the same vendor.
 *
 * For writing, most implementations come closer to standard than for schema
 * introspection, so we can write almost standard SQL in this implementation.
 * There are a few exceptions, documented along the way, sometime all vendors
 * have their own dialect, in this case, we almost always prefere PostgreSQL
 * in this class.
 *
 * @experimental
 */
abstract class SchemaManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        protected readonly DatabaseSession $session,
        protected readonly ?string $defaultSchema = null,
    ) {
        $this->setLogger(new NullLogger());
    }

    /**
     * Get default schema configured by user.
     */
    public function getDefaultSchema(): string
    {
        return $this->defaultSchema ?? $this->session->getDefaultSchema();
    }

    /**
     * Get database session.
     *
     * @internal
     *   In most cases, you should not use this, it will servce for some edge
     *   cases, such as transaction handling in the schema writer visitor.
     */
    public function getDatabaseSession(): DatabaseSession
    {
        return $this->session;
    }

    /**
     * Does this platform supports DDL transactions.
     */
    abstract public function supportsTransaction(): bool;

    /**
     * Does this platform supports schema.
     *
     * MySQL does not support schema (booooh).
     */
    public function supportsSchema(): bool
    {
        return true;
    }

    /**
     * Does this platform supports unsigned numeric values.
     *
     * Unsigned numeric values is NOT part of the SQL standard, until now only
     * MySQL and MariaDB seem to really support it. In a weird way.
     */
    public function supportsUnsigned(): bool
    {
        return false;
    }

    /**
     * List all databases.
     *
     * @return string[]
     */
    abstract public function listDatabases(): array;

    /**
     * List all schemas in the given database.
     *
     * @return string[]
     */
    abstract public function listSchemas(?string $database = null): array;

    /**
     * List all tables in given database and schema.
     *
     * If no schema given, use the default schema name.
     */
    public function listTables(?string $database = null, ?string $schema = null): array
    {
        $database ??= $this->session->getCurrentDatabase();
        $schema ??= $this->getDefaultSchema();

        return $this->doListTables($database, $schema);
    }

    /**
     * Does table exist.
     *
     * If no schema given, use the default schema name.
     */
    public function tableExists(string $name, ?string $database = null, ?string $schema = null): bool
    {
        $database ??= $this->session->getCurrentDatabase();
        list($schema, $name) = $this->detectSchema($name, $schema);

        return $this->doTableExists($database, $schema, $name);
    }

    /**
     * Get table information.
     *
     * If no schema given, use the default schema name.
     */
    public function getTable(string $name, ?string $database = null, ?string $schema = null): Table
    {
        $database ??= $this->session->getCurrentDatabase();
        list($schema, $name) = $this->detectSchema($name, $schema);

        if (!$this->tableExists($name, $database, $schema)) {
            throw new QueryBuilderError(\sprintf("Table '%s.%s.%s' does not exist", $database, $schema, $name));
        }

        return new Table(
            columns: $this->getTableColumns($database, $schema, $name),
            comment: $this->getTableComment($database, $schema, $name),
            database: $database,
            foreignKeys: $this->getTableForeignKeys($database, $schema, $name),
            indexes: $this->getTableIndexes($database, $schema, $name),
            name: $name,
            options: [],
            primaryKey: $this->getTablePrimaryKey($database, $schema, $name),
            reverseForeignKeys: $this->getTableReverseForeignKeys($database, $schema, $name),
            schema: $schema,
        );
    }

    /**
     * List all tables in given database and schema.
     */
    abstract protected function doListTables(string $database, string $schema): array;

    /**
     * Does table exist.
     */
    abstract protected function doTableExists(string $database, string $schema, string $name): bool;

    /**
     * Get table comment.
     */
    abstract protected function getTableComment(string $database, string $schema, string $name): ?string;

    /**
     * Get table columns.
     *
     * @return Column[]
     */
    abstract protected function getTableColumns(string $database, string $schema, string $name): array;

    /**
     * Get table primary key.
     */
    abstract protected function getTablePrimaryKey(string $database, string $schema, string $name): ?Key;

    /**
     * Get table foreign keys.
     *
     * @return ForeignKey[]
     */
    abstract protected function getTableForeignKeys(string $database, string $schema, string $name): array;

    /**
     * Get table reverse foreign keys.
     *
     * @return ForeignKey[]
     */
    abstract protected function getTableReverseForeignKeys(string $database, string $schema, string $name): array;

    /**
     * Get table indexes.
     *
     * @return Index[]
     */
    abstract protected function getTableIndexes(string $database, string $schema, string $name): array;

    /**
     * Start a transaction for schema manipulation.
     *
     * No real transaction will be started until the changes are applied.
     * If the database vendor doesn't support DDL statements transactions
     * then no transactions will be done at all.
     */
    public function modify(?string $schema = null): SchemaTransaction
    {
        return new SchemaTransaction(
            $schema ?? $this->getDefaultSchema(),
            function (SchemaTransaction $transaction) {
                $browser = new ChangeLogBrowser();
                $browser->addVisitor(new SchemaWriterLogVisitor($this));
                $browser->browse($transaction);
            },
        );
    }

    /**
     * Extract schema name from identifier.
     *
     * @return string[]
     *   First value is schema name, second is stripped identifier if it contained schema name.
     */
    protected function detectSchema(string $name, ?string $schema): array
    {
        if (null !== $schema) {
            return [$schema, $name];
        }
        if (false !== ($index = \strpos($name, '.'))) {
            return [\substr($name, 0, $index - 1), \substr($name, $index)];
        }
        return [$this->getDefaultSchema(), $name];
    }

    /**
     * Get the expression factory.
     */
    protected function expression(): ExpressionFactory
    {
        return new ExpressionFactory();
    }

    /**
     * Create a raw SQL expression.
     */
    protected function raw(string $expression, mixed $arguments = []): Expression
    {
        return $this->expression()->raw($expression, $arguments);
    }

    /**
     * Create a table expression.
     */
    protected function table(string|AbstractChange $name, ?string $schema = null): Expression
    {
        if (!\is_string($name)) {
            \assert($name instanceof AbstractChange);

            $schema ??= $name->getSchema();

            if ($name instanceof TableCreate || $name instanceof TableDrop || $name instanceof TableRename) {
                $name = $name->getName();
            } else if (\method_exists($name, 'getTable')) {
                $name = $name->getTable();
            } else {
                throw new QueryBuilderError("Change is not about a table.");
            }
        }

        if ($this->supportsSchema()) {
            if (null === $schema) {
                list($schema, $name) = $this->detectSchema($name, $schema);
            }
        } else {
            $schema = null;
        }

        return $this->expression()->table($name, null, $schema);
    }

    /**
     * Convert a column modify to a column add statement.
     *
     * @todo There are great chances this will change more things than expected.
     */
    protected function columnModifyToAdd(ColumnModify $change): ColumnAdd
    {
        $existing = $this
            ->getTable(
                database: $this->session->getCurrentDatabase(),
                name: $change->getTable(),
                schema: $change->getSchema(),
            )
            ->getColumn($change->getName())
        ;

        return new ColumnAdd(
            // @todo Do not change collation when default...
            collation: null, // $change->getCollation() ?? $existing->getCollation(),
            default: $change->isDropDefault() ? null : ($change->getDefault() ?? $existing->getDefault()),
            name: $change->getName(),
            nullable: $change->isNullable() ?? $existing->isNullable(),
            schema: $change->getSchema(),
            table: $change->getTable(),
            type: $change->getType() ?? $existing->getValueType(),
        );
    }

    /**
     * Write the COLLATE statement.
     *
     * @todo
     *   Maybe later, some normalization here with characters sets.
     */
    protected function doWriteColumnCollation(string $collation): Expression
    {
        return $this->raw('COLLATE ?::id', $collation);
    }

    /**
     * Proceed to any special default value treatment.
     *
     * @todo
     *   There are many, many cases to deal with:
     *     - string needs quoting,
     *     - numeric values need type information,
     *     - but there might be constants (such as CURRENT_TIME for example),
     *     - or function calls!
     */
    protected function doWriteColumnDefault(Type $type, string $default): Expression
    {
        return match ($type) {
            default => $this->raw($default),
        };
    }

    /**
     * Give a place where specific implementations might change type.
     *
     * @todo
     *   Here, we might want to plug over the converter in order to normalize
     *   type depending upon the RDBMS.
     */
    protected function doWriteColumnType(ColumnAdd|ColumnModify $change): Expression
    {
        if (!$type = $change->getType()) {
            throw new QueryBuilderError("You cannot change collation without specifying a new type.");
        }
        \assert($type instanceof Type);

        $pieces = [new DataType($type)];

        if ($collation = $change->getCollation()) {
            $pieces[] = $this->doWriteColumnCollation($collation);
        }

        return $this->raw(\implode(' ', \array_fill(0, \count($pieces), '?')), [...$pieces]);
    }

    /**
     * Write identity suffix for column.
     */
    protected function doWriteColumnIdentity(Type $type): ?Expression
    {
        return $this->raw('GENERATED ALWAYS AS IDENTITY');
    }

    /**
     * Write identity suffix for serial.
     */
    protected function doWriteColumnSerial(Type $type): ?Expression
    {
        return $this->raw('GENERATED BY DEFAULT AS IDENTITY');
    }

    /**
     * Override if standard SQL is not enough.
     *
     * Column specification.
     */
    protected function doWriteColumn(ColumnAdd $change): Expression
    {
        $pieces = [];

        if (!$change->isNullable()) {
            $pieces[] = $this->raw('NOT NULL');
        }

        $type = $change->getType();
        if ($type->isIdentity() && ($expr = $this->doWriteColumnIdentity($type))) {
            $pieces[] = $expr;
        } else if ($type->isSerial() && ($expr = $this->doWriteColumnSerial($type))) {
            $pieces[] = $expr;
        }

        if ($default = $change->getDefault()) {
            $pieces[] = $this->raw('DEFAULT ?', [$this->doWriteColumnDefault($change->getType(), $default)]);
        }

        return $this->raw(
            '?::id ? ' . \implode(' ', \array_fill(0, \count($pieces), '?')),
            [
                $change->getName(),
                $this->doWriteColumnType($change),
                ...$pieces,
            ],
        );
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeColumnAdd(ColumnAdd $change): iterable|Expression
    {
        return $this->raw('ALTER TABLE ? ADD COLUMN ?', [$this->table($change), $this->doWriteColumn($change)]);
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @see https://www.postgresql.org/docs/current/sql-altertable.html
     *   PostgreSQL uses ALTER TABLE x ALTER COLUMN y
     * @see https://learn.microsoft.com/en-us/sql/t-sql/statements/alter-table-transact-sql?view=sql-server-ver16
     *   SQLServer uses ALTER TABLE x ALTER COLUMN y
     * @see https://dev.mysql.com/doc/refman/8.0/en/alter-table.html
     *   MySQL uses ALTER TABLE x ALTER COLUMN y
     * @see https://sqlite.org/lang_altertable.html
     * @see https://stackoverflow.com/questions/2685885/sqlite-modify-column
     *   SQLite requires a ADD/UPDATE/DROP/RENAME algorithm for this to work.
     *
     * This implementation is PostgreSQL friendly, all others will have their
     * own I guess.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeColumnModify(ColumnModify $change): iterable|Expression
    {
        $name = $change->getName();
        $pieces = [];

        if ($change->isDropDefault()) {
            if ($change->getDefault()) {
                // @todo Find a way to have a "identification method" on changes.
                throw new QueryBuilderError(\sprintf("Column '%s': you cannot drop default and set default at the same time.", $name));
            }
            $pieces[] = $this->raw('ALTER COLUMN ?::id DROP DEFAULT', [$name]);
        } else if ($default = $change->getDefault()) {
            $pieces[] = $this->raw('ALTER COLUMN ?::id SET DEFAULT ?', [$name, $this->raw($default)]);
        }

        if ($change->getType() || $change->getCollation()) {
            $pieces[] = $this->raw('ALTER COLUMN ?::id TYPE ?', [$name, $this->doWriteColumnType($change)]);
        }

        if (null !== ($nullable = $change->isNullable())) {
            if ($nullable) {
                $pieces[] = $this->raw('ALTER COLUMN ?::id DROP NOT NULL', [$name]);
            } else {
                $pieces[] = $this->raw('ALTER COLUMN ?::id SET NOT NULL', [$name]);
            }
        }

        if (!$pieces) {
            throw new QueryBuilderError(\sprintf("Column '%s': no changes are requested.", $name));
        }

        return $this->raw('ALTER TABLE ? ' . \implode(', ', \array_fill(0, \count($pieces), '?')), [$this->table($change), ...$pieces]);
    }

    /**
     * Override if standard SQL is not enough.
     *
     * PostgreSQL needs to override for CASCADE support.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeColumnDrop(ColumnDrop $change): iterable|Expression
    {
        if ($change->isCascade()) {
            // @todo Implement this with PostgreSQL.
            throw new UnsupportedFeatureError("ALTER TABLE x DROP COLUMN y CASCADE is not supported by this vendor.");
        }

        return $this->raw('ALTER TABLE ? DROP COLUMN ?::id', [$this->table($change), $change->getName()]);
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @see https://learn.microsoft.com/en-us/sql/relational-databases/tables/rename-columns-database-engine?view=sql-server-ver16
     *   SQLServer will need something more complex.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeColumnRename(ColumnRename $change): iterable|Expression
    {
        return $this->raw('ALTER TABLE ? RENAME COLUMN ?::id TO ?::id', [$this->table($change), $change->getName(), $change->getNewName()]);
    }

    /**
     * Override if standard SQL is not enough.
     *
     * Constraint specification.
     */
    protected function doWriteConstraint(?string $name, Expression $spec): Expression
    {
        if ($name) {
            return $this->raw('CONSTRAINT ?::id ?', [$name, $spec]);
        }
        return $spec;
    }

    /**
     * Override if standard SQL is not enough.
     *
     * Drop any constraint behaviour.
     *
     * @todo SQLite does not support this and requires a table copy.
     */
    protected function doWriteConstraintDrop(string $name, string $table, string $schema): Expression
    {
        return $this->raw('ALTER TABLE ? DROP CONSTRAINT ?::id', [$this->table($table, $schema), $name]);
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @todo SQLite does not support this and requires a table copy.
     */
    protected function writeConstraintDrop(ConstraintDrop $change): iterable|Expression
    {
        return $this->doWriteConstraintDrop($change->getName(), $change->getTable(), $change->getSchema());
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeConstraintModify(ConstraintModify $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("Not implemented yet. For most vendors, this requires a drop/add, you can temporary workaround by dropping then adding back the constraint.");
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeConstraintRename(ConstraintRename $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("Not implemented yet. For most vendors, this requires a drop/add, you can temporary workaround by dropping then adding back the constraint.");
    }

    /**
     * Write the "CASCADE|NO ACTION|RESTRICT|SET DEFAULT|SET NULL" FOREIGN KEY clause.
     */
    protected function doWriteForeignKeyBehavior(string $behavior): Expression
    {
        return match ($behavior) {
            ForeignKeyAdd::ON_DELETE_CASCADE, ForeignKeyAdd::ON_UPDATE_CASCADE => $this->raw('CASCADE'),
            ForeignKeyAdd::ON_DELETE_NO_ACTION, ForeignKeyAdd::ON_UPDATE_NO_ACTION => $this->raw('NO ACTION'),
            ForeignKeyAdd::ON_DELETE_RESTRICT, ForeignKeyAdd::ON_UPDATE_RESTRICT => $this->raw('RESTRICT'),
            ForeignKeyAdd::ON_DELETE_SET_DEFAULT, ForeignKeyAdd::ON_UPDATE_SET_DEFAULT => $this->raw('SET DEFAULT'),
            ForeignKeyAdd::ON_DELETE_SET_NULL, ForeignKeyAdd::ON_UPDATE_SET_NULL => $this->raw('SET NULL'),
            default => $behavior,
        };
    }

    /**
     * Write the "INITIALLY DEFERRED|IMMEDIATE" FOREIGN KEY clause.
     */
    protected function doWriteForeignKeyInitially(string $initially): Expression
    {
        return match ($initially) {
            ForeignKeyAdd::INITIALLY_DEFERRED, ForeignKeyAdd::INITIALLY_DEFERRED => $this->raw('INITIALLY DEFERRED'),
            ForeignKeyAdd::INITIALLY_IMMEDIATE, ForeignKeyAdd::INITIALLY_IMMEDIATE => $this->raw('INITIALLY IMMEDIATE'),
            default => 'initially ' . $initially,
        };
    }

    /**
     * Write FOREIGN KEY complete clause, without the "ADD CONSTRAINT name" prefix.
     *
     * Foreign key constraint specification.
     */
    protected function doWriteForeignKey(ForeignKeyAdd $change): Expression
    {
        $suffix = [];
        if ($deleteBehaviour = $change->getOnDelete()) {
            $suffix[] = $this->raw('ON DELETE ?', $this->doWriteForeignKeyBehavior($deleteBehaviour));
        }
        if ($updateBehaviour = $change->getOnUpdate()) {
            $suffix[] = $this->raw('ON UPDATE ?', $this->doWriteForeignKeyBehavior($updateBehaviour));
        }
        if ($change->isDeferrable()) {
            $suffix[] = $this->raw('DEFERRABLE');
        } else {
            $suffix[] = $this->raw('NOT DEFERRABLE');
        }
        if ($initially = $change->getInitially()) {
            $suffix[] = $this->doWriteForeignKeyInitially($initially);
        }

        return $this->doWriteConstraint(
            $change->getName(),
            $this->raw(
                'FOREIGN KEY (?::id[]) REFERENCES ? (?::id[]) ' . \implode('', \array_fill(0, \count($suffix), ' ?')),
                [
                    $change->getColumns(),
                    $this->table($change->getForeignTable(), $change->getForeignSchema() ?? $change->getSchema()),
                    $change->getForeignColumns(),
                    ...$suffix,
                ],
            ),
        );
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeForeignKeyAdd(ForeignKeyAdd $change): iterable|Expression
    {
        return $this->raw('ALTER TABLE ? ADD ?', [$this->table($change), $this->doWriteForeignKey($change)]);
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeForeignKeyModify(ForeignKeyModify $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("Not implemented yet. For most vendors, this requires a drop/add, you can temporary workaround by dropping then adding back the constraint.");
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeForeignKeyDrop(ForeignKeyDrop $change): iterable|Expression
    {
        return $this->doWriteConstraintDrop($change->getName(), $change->getTable(), $change->getSchema());
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeForeignKeyRename(ForeignKeyRename $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("Not implemented yet. For most vendors, this requires a drop/add, you can temporary workaround by dropping then adding back the constraint.");
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeIndexCreate(IndexCreate $change): iterable|Expression
    {
        if ($change->getName()) {
            return $this->raw('CREATE INDEX ?::id ON ? (?::id[])', [$change->getName(), $this->table($change), $change->getColumns()]);
        }
        return $this->raw('CREATE INDEX ON ? (?::id[])', [$this->table($change), $change->getColumns()]);
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeIndexDrop(IndexDrop $change): iterable|Expression
    {
        return $this->raw('DROP INDEX ?', $this->table($change->getName(), $change->getSchema()));
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @see https://stackoverflow.com/questions/1463363/how-do-i-rename-an-index-in-mysql
     *   MySQL will be easy.
     * @see https://www.postgresql.org/docs/current/sql-alterindex.html
     *   PostgreSQL makes me happy.
     * @see https://stackoverflow.com/questions/42530689/how-to-rename-an-index-in-sqlite
     *   SQLite will need a DROP/ADD.
     * @see https://learn.microsoft.com/en-us/sql/relational-databases/indexes/rename-indexes?view=sql-server-ver16
     *   SQLServer will need some oil to do this.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeIndexRename(IndexRename $change): iterable|Expression
    {
        throw new UnsupportedFeatureError("No standard here, everyone has its own syntax.");
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writePrimaryKeyAdd(PrimaryKeyAdd $change): iterable|Expression
    {
        return $this->raw('ALTER TABLE ? ADD ?', [$this->table($change), $this->doWritePrimaryKey($change)]);
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @todo SQLite does not support this and requires a table copy.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writePrimaryKeyDrop(PrimaryKeyDrop $change): iterable|Expression
    {
        return $this->doWriteConstraintDrop($change->getName(), $change->getTable(), $change->getSchema());
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function doWritePrimaryKey(PrimaryKeyAdd $change): Expression
    {
        return $this->doWriteConstraint(
            $change->getName(),
            $this->raw('PRIMARY KEY (?::id[])', [$change->getColumns()]),
        );
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function doWriteTableCreateUniqueKey(UniqueKeyAdd $change): Expression
    {
        if (!$change->isNullsDistinct()) {
            // @todo Implement this with PostgreSQL.
            throw new UnsupportedFeatureError("UNIQUE NULLS NOT DISTINCT is not supported by this vendor.");
        }

        $spec = $this->raw('UNIQUE (?::id[])', [$change->getColumns()]);

        return $this->doWriteConstraint($change->getName(), $spec);
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeTableCreate(TableCreate $change): iterable|Expression
    {
        $pieces = \array_map($this->doWriteColumn(...), $change->getColumns());
        if ($primaryKey = $change->getPrimaryKey()) {
            $pieces[] = $this->doWritePrimaryKey($primaryKey);
        }
        foreach ($change->getUniqueKeys() as $uniqueKey) {
            $pieces[] = $this->doWriteTableCreateUniqueKey($uniqueKey);
        }
        foreach ($change->getForeignKeys() as $foreignKey) {
            $pieces[] = $this->doWriteForeignKey($foreignKey);
        }

        $placeholder = \implode(', ', \array_fill(0, \count($pieces), '?'));

        if ($change->isTemporary()) {
            yield $this->raw('CREATE TEMPORARY TABLE ? (' . $placeholder . ')', [$this->table($change), ...$pieces]);
        } else {
            yield $this->raw('CREATE TABLE ? (' . $placeholder . ')', [$this->table($change), ...$pieces]);
        }

        foreach ($change->getIndexes() as $index) {
            yield $this->writeIndexCreate($index);
        }
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeTableDrop(TableDrop $change): iterable|Expression
    {
        if ($change->isCascade()) {
            // @todo Implement this with PostgreSQL.
            throw new UnsupportedFeatureError("UNIQUE NULLS NOT DISTINCT is not supported by this vendor.");
        }

        return $this->raw('DROP TABLE ?', $this->table($change->getName(), $change->getSchema()));
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeTableRename(TableRename $change): iterable|Expression
    {
        return $this->raw('ALTER TABLE ? RENAME TO ?::id', [$this->table($change->getName(), $change->getSchema()), $change->getNewName()]);
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeUniqueKeyAdd(UniqueKeyAdd $change): iterable|Expression
    {
        if (!$change->isNullsDistinct()) {
            // @todo Implement this with PostgreSQL.
            throw new UnsupportedFeatureError("UNIQUE NULLS NOT DISTINCT is not supported by this vendor.");
        }

        if ($name = $change->getName()) {
            return $this->raw('CREATE UNIQUE INDEX ?::id ON ? (?::id[])', [$name, $this->table($change), $change->getColumns()]);
        }

        return $this->raw('CREATE UNIQUE INDEX ON ? (?::id[])', [$this->table($change), $change->getColumns()]);
    }

    /**
     * There is nothing to override here.
     */
    protected function executeCallbackChange(CallbackChange $change): void
    {
        ($change->getCallback())($this->getDatabaseSession());
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @todo SQLite does not support this and requires a table copy.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeUniqueKeyDrop(UniqueKeyDrop $change): iterable|Expression
    {
        return $this->raw('DROP INDEX ?::id', [$change->getName()]);
    }

    /**
     * There is nothing to override here.
     */
    protected function evaluateConditionCallbackCondition(CallbackCondition $condition): bool
    {
        return (bool) ($condition->getCallback())($this->getDatabaseSession());
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function evaluateConditionColumnExists(ColumnExists $condition): bool
    {
        try {
            return \in_array(
                $condition->getColumn(),
                $this
                    ->getTable(
                        database: $this->session->getCurrentDatabase(),
                        name: $condition->getTable(),
                        schema: $condition->getSchema(),
                    )
                    ->getColumnNames()
            );
        } catch (TableDoesNotExistError) {
            return false;
        }
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function evaluateConditionIndexExists(IndexExists $condition): bool
    {
        throw new UnsupportedFeatureError("Not implemented yet.");
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function evaluateConditionTableExists(TableExists $condition): bool
    {
        return $this->tableExists(
            database: $this->session->getCurrentDatabase(),
            name: $condition->getTable(),
            schema: $condition->getSchema(),
        );
    }

    /**
     * Condition evualation.
     *
     * @internal
     *   For SchemaWriterLogVisitor usage only.
     */
    public function evaluateCondition(AbstractCondition $condition): bool
    {
        $matches = (bool) match (\get_class($condition)) {
            CallbackCondition::class => $this->evaluateConditionCallbackCondition($condition),
            ColumnExists::class => $this->evaluateConditionColumnExists($condition),
            IndexExists::class => $this->evaluateConditionIndexExists($condition),
            TableExists::class => $this->evaluateConditionTableExists($condition),
            default => throw new QueryBuilderError(\sprintf("Unsupported condition: %s", \get_class($condition))),
        };

        return $condition->isNegation() ? !$matches : $matches;
    }

    /**
     * Apply a given change in the current schema.
     *
     * @internal
     *   For SchemaWriterLogVisitor usage only.
     */
    public function applyChange(AbstractChange $change): void
    {
        $expressions = match (\get_class($change)) {
            CallbackChange::class => $this->executeCallbackChange($change),
            ColumnAdd::class => $this->writeColumnAdd($change),
            ColumnDrop::class => $this->writeColumnDrop($change),
            ColumnModify::class => $this->writeColumnModify($change),
            ColumnRename::class => $this->writeColumnRename($change),
            ConstraintDrop::class => $this->writeConstraintDrop($change),
            ConstraintModify::class => $this->writeConstraintModify($change),
            ConstraintRename::class => $this->writeConstraintRename($change),
            ForeignKeyAdd::class => $this->writeForeignKeyAdd($change),
            ForeignKeyDrop::class => $this->writeForeignKeyDrop($change),
            ForeignKeyModify::class => $this->writeForeignKeyModify($change),
            ForeignKeyRename::class => $this->writeForeignKeyRename($change),
            IndexCreate::class => $this->writeIndexCreate($change),
            IndexDrop::class => $this->writeIndexDrop($change),
            IndexRename::class => $this->writeIndexRename($change),
            PrimaryKeyAdd::class => $this->writePrimaryKeyAdd($change),
            PrimaryKeyDrop::class => $this->writePrimaryKeyDrop($change),
            TableCreate::class => $this->writeTableCreate($change),
            TableDrop::class => $this->writeTableDrop($change),
            TableRename::class => $this->writeTableRename($change),
            UniqueKeyAdd::class => $this->writeUniqueKeyAdd($change),
            UniqueKeyDrop::class => $this->writeUniqueKeyDrop($change),
            default => throw new QueryBuilderError(\sprintf("Unsupported alteration operation: %s", \get_class($change))),
        };

        if ($expressions) {
            foreach (\is_iterable($expressions) ? $expressions : [$expressions] as $expression) {
                $this->session->executeStatement($expression);
            }
        }
    }
}
