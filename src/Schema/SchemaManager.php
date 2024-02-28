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
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueConstraintAdd;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueConstraintDrop;


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
    protected function table(string $name, ?string $schema = null): Expression
    {
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
     * Drop any constraint behaviour.
     */
    protected function writeConstraintDropSpec(string $name, string $table, string $schema): Expression
    {
        return $this->raw('ALTER TABLE ? DROP CONSTRAINT ?::identifier', [$this->table($table, $schema), $name]);
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
                    '?::identifier ? DEFAULT ?',
                    [
                        $change->getName(),
                        $this->writeColumnSpecType($type),
                        $this->writeColumnSpecDefault($type, $default)
                    ]
                );
            }

            return $this->raw(
                '?::identifier ?',
                [
                    $change->getName(),
                    $this->writeColumnSpecType($type),
                ]
            );
        }

        if ($default) {
            return $this->raw(
                '?::identifier ? NOT NULL DEFAULT ?',
                [
                    $change->getName(),
                    $this->writeColumnSpecType($type),
                    $this->writeColumnSpecDefault($type, $default)
                ]
            );
        }

        return $this->raw(
            '?::identifier ? NOT NULL',
            [
                $change->getName(),
                $this->writeColumnSpecType($type)
            ]
        );
    }

    /**
     * Override if standard SQL is not enough.
     *
     * Foreign key behaviour.
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
     * Override if standard SQL is not enough.
     *
     * Foreign key initial behaviour.
     */
    protected function writeForeignKeySpecInitially(string $initially): Expression
    {
        return match ($initially) {
            ForeignKeyAdd::INITIALLY_DEFERRED, ForeignKeyAdd::INITIALLY_DEFERRED => $this->raw('INITIALLY DEFERRED'),
            ForeignKeyAdd::INITIALLY_IMMEDIATE, ForeignKeyAdd::INITIALLY_IMMEDIATE => $this->raw('INITIALLY IMMEDIATE'),
        };
    }

    /**
     * Override if standard SQL is not enough.
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
                'FOREIGN KEY (?::identifier[]) REFERENCES ? (?::identifier[]) ' . \implode('', \array_fill(0, \count($suffix), ' ?')),
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
     * Constraint specification.
     */
    protected function writeConstraintSpec(?string $name, Expression $spec): Expression
    {
        if ($name) {
            return $this->raw('CONSTRAINT ?::identifier ?', [$name, $spec]);
        }
        return $spec;
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeColumnAdd(ColumnAdd $change): Expression
    {
        return $this->raw(
            'ALTER TABLE ? ADD COLUMN ?',
            [
                $this->table($change->getTable(), $change->getSchema()),
                $this->writeColumnSpec($change),
            ]
        );
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeColumnModify(ColumnModify $change): Expression
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Override if standard SQL is not enough.
     *
     * PostgreSQL needs to override for CASCADE support.
     */
    protected function writeColumnDrop(ColumnDrop $change): Expression
    {
        if ($change->isCascade()) {
            // @todo Implement this with PostgreSQL.
            throw new UnsupportedFeatureError("ALTER TABLE x DROP COLUMN y CASCADE is not supported by this vendor.");
        }

        return $this->raw(
            'ALTER TABLE ? DROP COLUMN ?::identifier',
            [
                $this->table($change->getTable(), $change->getSchema()),
                $change->getName(),
            ]
        );
    }

    /**
     * Override if standard SQL is not enough.
     *
     * SQLServer does not seem to implement RENAME COLUMN x TO y.
     */
    protected function writeColumnRename(ColumnRename $change): Expression
    {
        return $this->raw(
            'ALTER TABLE ? RENAME COLUMN ?::identifier TO ?::identifier',
            [
                $this->table($change->getTable(), $change->getSchema()),
                $change->getNewName(),
            ]
        );
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeConstraintDrop(ConstraintDrop $change): Expression
    {
        return $this->writeConstraintDropSpec($change->getName(), $change->getTable(), $change->getSchema());
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeConstraintModify(ConstraintModify $change): Expression
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeConstraintRename(ConstraintRename $change): Expression
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeForeignKeyAdd(ForeignKeyAdd $change): Expression
    {
        return $this->raw(
            'ALTER TABLE ? ADD ?',
            [
                $this->table($change->getTable(), $change->getSchema()),
                $this->writeForeignKeySpec($change),
            ]
        );
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeForeignKeyModify(ForeignKeyModify $change): Expression
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeForeignKeyDrop(ForeignKeyDrop $change): Expression
    {
        return $this->writeConstraintDropSpec($change->getName(), $change->getTable(), $change->getSchema());
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeForeignKeyRename(ForeignKeyRename $change): Expression
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeIndexCreate(IndexCreate $change): Expression
    {
        if ($change->getName()) {
            return $this->raw(
                'CREATE INDEX ?::identifier ON ? (?::identifier[])',
                [
                    $change->getName(),
                    $this->table($change->getTable(), $change->getSchema()),
                    $change->getColumns(),
                ]
            );
        }

        return $this->raw(
            'CREATE INDEX ON ? (?::identifier[])',
            [
                $this->table($change->getTable(), $change->getSchema()),
                $change->getColumns(),
            ]
        );
    }

    /**
     * Override if standard SQL is not enough.
     *
     * This syntax should work with:
     *   - PostgreSQL
     *   - SQLite
     *   - SQLServer
     */
    protected function writeIndexDrop(IndexDrop $change): Expression
    {
        return $this->raw(
            'DROP INDEX ?',
            [
                $this->table($change->getName(), $change->getSchema()),
            ]
        );
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeIndexRename(IndexRename $change): Expression
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writePrimaryKeyAdd(PrimaryKeyAdd $change): Expression
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writePrimaryKeyDrop(PrimaryKeyDrop $change): Expression
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeTableCreate(TableCreate $change): Expression
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeTableDrop(TableDrop $change): Expression
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeTableRename(TableRename $change): Expression
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeUniqueConstraintAdd(UniqueConstraintAdd $change): Expression
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Override if standard SQL is not enough.
     */
    protected function writeUniqueConstraintDrop(UniqueConstraintDrop $change): Expression
    {
        return $this->writeConstraintDropSpec($change->getName(), $change->getTable(), $change->getSchema());
    }

    /**
     * Apply a given change in the current schema.
     */
    protected function apply(AbstractChange $change): void
    {
        $expression = match (\get_class($change)) {
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
            UniqueConstraintAdd::class => $this->writeUniqueConstraintAdd($change),
            UniqueConstraintDrop::class => $this->writeUniqueConstraintDrop($change),
            default => throw new QueryBuilderError(\sprintf("Unsupported alteration operation: %s", \get_class($change))),
        };

        $this->executeChange($change, $expression);
    }

    /**
     * Really execute the change.
     */
    protected function executeChange(AbstractChange $change, Expression $expression): void
    {
        $this->queryExecutor->executeStatement($expression);
    }
}
