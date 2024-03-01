<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\QueryExecutor;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Error\UnsupportedFeatureError;
use MakinaCorpus\QueryBuilder\Schema\Diff\AbstractChange;
use MakinaCorpus\QueryBuilder\Schema\Diff\ChangeLog;
use MakinaCorpus\QueryBuilder\Schema\Diff\SchemaTransaction;
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

/**
 * Schema alteration SQL statements in this class are mostly standard, but not
 * quite. They basically are the lowest common denominator between PostgreSQL,
 * SQLite and SQLServer. All three are very conservative with SQL standard so
 * basically, the intersection of three is what we have closest to it.
 */
abstract class SchemaManager
{
    public function __construct(
        protected readonly QueryExecutor $queryExecutor,
    ) {}

    /**
     * Does this platform supports DDL transactions.
     */
    public abstract function supportsTransaction(): bool;

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
    public abstract function listDatabases(): array;

    /**
     * List all schemas in the given database.
     *
     * @return string[]
     */
    public abstract function listSchemas(string $database): array;

    /**
     * List all tables in given database and schema.
     */
    public abstract function listTables(string $database, string $schema = 'public'): array;

    /**
     * Does table exist.
     */
    public abstract function tableExists(string $database, string $name, string $schema = 'public'): bool;

    /**
     * Get table information.
     */
    public function getTable(string $database, string $name, string $schema = 'public'): Table
    {
        if (!$this->tableExists($database, $name, $schema)) {
            throw new QueryBuilderError(\sprintf("Table '%s.%s.%s' does not exist", $database, $schema, $name));
        }

        return new Table(
            columns: $this->getTableColumns($database, $name, $schema),
            comment: $this->getTableComment($database, $name, $schema),
            database: $database,
            foreignKeys: $this->getTableForeignKeys($database, $name, $schema),
            name: $name,
            options: [],
            primaryKey: $this->getTablePrimaryKey($database, $name, $schema),
            reverseForeignKeys: $this->getTableReverseForeignKeys($database, $name, $schema),
            schema: $schema,
        );
    }

    /**
     * Get table comment.
     */
    protected abstract function getTableComment(string $database, string $name, string $schema = 'public'): ?string;

    /**
     * Get table columns.
     *
     * @return Column[]
     */
    protected abstract function getTableColumns(string $database, string $name, string $schema = 'public'): array;

    /**
     * Get table primary key.
     */
    protected abstract function getTablePrimaryKey(string $database, string $name, string $schema = 'public'): ?Key;

    /**
     * Get table foreign keys.
     *
     * @return ForeignKey[]
     */
    protected abstract function getTableForeignKeys(string $database, string $name, string $schema = 'public'): array;

    /**
     * Get table reverse foreign keys.
     *
     * @return ForeignKey[]
     */
    protected abstract function getTableReverseForeignKeys(string $database, string $name, string $schema = 'public'): array;

    /**
     * Start a transaction for schema manipulation.
     *
     * No real transaction will be started until the changes are applied.
     * If the database vendor doesn't support DDL statements transactions
     * then no transactions will be done at all.
     */
    public function modify(string $database, string $schema = 'public'): SchemaTransaction
    {
        return new SchemaTransaction(
            $this,
            $database,
            $schema,
            function (ChangeLog $changeLog) {
                // @todo start transaction
                foreach ($changeLog->getAll() as $change) {
                    $this->apply($change);
                }
                // @todo end transaction
            }
        );
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

        if (!$this->supportsSchema()) {
            $schema = null;
        }

        return $this->expression()->table($name, null, $schema);
    }

    /**
     * Give a place where specific implementations might change type.
     *
     * @todo
     *   Here, we might want to plug over the converter in order to normalize
     *   type depending upon the RDBMS.
     */
    protected function writeColumnSpecType(string $type): Expression
    {
        return $this->raw($type);
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
    protected function writeColumnSpecDefault(string $type, string $default): Expression
    {
        return match ($type) {
            default => $this->raw($default),
        };
    }

    /**
     * Override if standard SQL is not enough.
     *
     * Column specification.
     */
    protected function writeColumnSpec(ColumnAdd $change): Expression
    {
        $type = $change->getType();
        $default = $change->getDefault();

        if ($change->isNullable()) {
            if ($default) {
                return $this->raw(
                    '?::id ? DEFAULT ?',
                    [
                        $change->getName(),
                        $this->writeColumnSpecType($type),
                        $this->writeColumnSpecDefault($type, $default)
                    ]
                );
            }

            return $this->raw(
                '?::id ?',
                [
                    $change->getName(),
                    $this->writeColumnSpecType($type),
                ]
            );
        }

        if ($default) {
            return $this->raw(
                '?::id ? NOT NULL DEFAULT ?',
                [
                    $change->getName(),
                    $this->writeColumnSpecType($type),
                    $this->writeColumnSpecDefault($type, $default)
                ]
            );
        }

        return $this->raw(
            '?::id ? NOT NULL',
            [
                $change->getName(),
                $this->writeColumnSpecType($type)
            ]
        );
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeColumnAdd(ColumnAdd $change): iterable|Expression
    {
        return $this->raw('ALTER TABLE ? ADD COLUMN ?', [$this->table($change), $this->writeColumnSpec($change)]);
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeColumnModify(ColumnModify $change): iterable|Expression
    {
        throw new \Exception("Not implemented yet.");
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
    protected function writeConstraintSpec(?string $name, Expression $spec): Expression
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
    protected function writeConstraintDropSpec(string $name, string $table, string $schema): Expression
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
        return $this->writeConstraintDropSpec($change->getName(), $change->getTable(), $change->getSchema());
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
    protected function writeForeignKeySpecBehavior(string $behavior): Expression
    {
        return match ($behavior) {
            ForeignKeyAdd::ON_DELETE_CASCADE, ForeignKeyAdd::ON_UPDATE_CASCADE => $this->raw('CASCADE'),
            ForeignKeyAdd::ON_DELETE_NO_ACTION, ForeignKeyAdd::ON_UPDATE_NO_ACTION => $this->raw('NO ACTION'),
            ForeignKeyAdd::ON_DELETE_RESTRICT, ForeignKeyAdd::ON_UPDATE_RESTRICT => $this->raw('RESTRICT'),
            ForeignKeyAdd::ON_DELETE_SET_DEFAULT, ForeignKeyAdd::ON_UPDATE_SET_DEFAULT => $this->raw('SET DEFAULT'),
            ForeignKeyAdd::ON_DELETE_SET_NULL, ForeignKeyAdd::ON_UPDATE_SET_NULL => $this->raw('SET NULL'),
        };
    }

    /**
     * Write the "INITIALLY DEFERRED|IMMEDIATE" FOREIGN KEY clause.
     */
    protected function writeForeignKeySpecInitially(string $initially): Expression
    {
        return match ($initially) {
            ForeignKeyAdd::INITIALLY_DEFERRED, ForeignKeyAdd::INITIALLY_DEFERRED => $this->raw('INITIALLY DEFERRED'),
            ForeignKeyAdd::INITIALLY_IMMEDIATE, ForeignKeyAdd::INITIALLY_IMMEDIATE => $this->raw('INITIALLY IMMEDIATE'),
        };
    }

    /**
     * Write FOREIGN KEY complete clause, without the "ADD CONSTRAINT name" prefix.
     *
     * Foreign key constraint specification.
     */
    protected function writeForeignKeySpec(ForeignKeyAdd $change): Expression
    {
        $suffix = [];
        if ($deleteBehaviour = $change->getOnDelete()) {
            $suffix[] = $this->raw('ON DELETE ?', $this->writeForeignKeySpecBehavior($deleteBehaviour));
        }
        if ($updateBehaviour = $change->getOnUpdate()) {
            $suffix[] = $this->raw('ON UPDATE ?', $this->writeForeignKeySpecBehavior($updateBehaviour));
        }
        if ($change->isDeferrable()) {
            $suffix[] = $this->raw('DEFERRABLE');
        } else {
            $suffix[] = $this->raw('NOT DEFERRABLE');
        }
        if ($initially = $change->getInitially()) {
            $suffix[] = $this->writeForeignKeySpecInitially($initially);
        }

        return $this->writeConstraintSpec(
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
        return $this->raw('ALTER TABLE ? ADD ?', [$this->table($change), $this->writeForeignKeySpec($change)]);
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
        return $this->writeConstraintDropSpec($change->getName(), $change->getTable(), $change->getSchema());
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
        return $this->raw('DROP INDEX ?::id.?::id', [$change->getSchema(), $change->getName()]);
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
        return $this->raw('ALTER TABLE ? ADD ?', [$this->table($change), $this->writePrimaryKeySpec($change)]);
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
        return $this->writeConstraintDropSpec($change->getName(), $change->getTable(), $change->getSchema());
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writePrimaryKeySpec(PrimaryKeyAdd $change): Expression
    {
        return $this->writeConstraintSpec(
            $change->getName(),
            $this->raw('PRIMARY KEY (?::id[])', [$change->getColumns()]),
        );
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeTableCreateSpecUniqueKey(UniqueKeyAdd $change): Expression
    {
        if (!$change->isNullsDistinct()) {
            // @todo Implement this with PostgreSQL.
            throw new UnsupportedFeatureError("UNIQUE NULLS NOT DISTINCT is not supported by this vendor.");
        }

        $spec = $this->raw('UNIQUE (?::id[])', [$change->getColumns()]);

        return $this->writeConstraintSpec($change->getName(), $spec);
    }

    /**
     * Override if standard SQL is not enough.
     *
     * @return Expression|iterable<Expression>
     */
    protected function writeTableCreate(TableCreate $change): iterable|Expression
    {
        $pieces = \array_map($this->writeColumnSpec(...), $change->getColumns());
        if ($primaryKey = $change->getPrimaryKey()) {
            $pieces[] = $this->writePrimaryKeySpec($primaryKey);
        }
        foreach ($change->getUniqueKeys() as $uniqueKey) {
            $pieces[] = $this->writeTableCreateSpecUniqueKey($uniqueKey);
        }
        foreach ($change->getForeignKeys() as $foreignKey) {
            $pieces[] = $this->writeForeignKeySpec($foreignKey);
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
     * Apply a given change in the current schema.
     */
    protected function apply(AbstractChange $change): void
    {
        $expressions = match (\get_class($change)) {
            ColumnAdd::class => $this->writeColumnAdd($change),
            ColumnModify::class => $this->writeColumnModify($change),
            ColumnDrop::class => $this->writeColumnDrop($change),
            ColumnRename::class => $this->writeColumnRename($change),
            ConstraintDrop::class => $this->writeConstraintDrop($change),
            ConstraintModify::class => $this->writeConstraintModify($change),
            ConstraintRename::class => $this->writeConstraintRename($change),
            ForeignKeyAdd::class => $this->writeForeignKeyAdd($change),
            ForeignKeyModify::class => $this->writeForeignKeyModify($change),
            ForeignKeyDrop::class => $this->writeForeignKeyDrop($change),
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

        $this->executeChange($change, $expressions);
    }

    /**
     * Really execute the change.
     *
     * @param Expression|iterable<Expression> $expressions
     */
    protected function executeChange(AbstractChange $change, iterable|Expression $expressions): void
    {
        foreach (\is_iterable($expressions) ? $expressions : [$expressions] as $expression) {
            $this->queryExecutor->executeStatement($expression);
        }
    }
}
