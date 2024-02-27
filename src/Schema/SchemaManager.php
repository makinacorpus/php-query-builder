<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\QueryExecutor;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableRename;
use MakinaCorpus\QueryBuilder\Schema\Diff\SchemaTransaction;

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
    public abstract function getTable(string $database, string $name, string $schema = 'public'): Table;

    /**
     * Start a transaction for schema manipulation.
     *
     * No real transaction will be started until the changes are applied.
     * If the database vendor doesn't support DDL statements transactions
     * then no transactions will be done at all.
     */
    public function modify(string $database): SchemaTransaction
    {
        return new SchemaTransaction($this, $database);
    }

    /**
     * Apply a given change in the current schema.
     */
    protected function apply(Change $change): void
    {
        match (\get_class($change)) {
            ColumnRename::class => $this->applyRenameColumn(),
            TableRename::class => $this->applyRenameTable(),
            default => throw new QueryBuilderError(\sprintf("Unsupported alteration operation: %s", \get_class($change))),
        };
    }

    protected function executeStatements(array $statements)
    {
        
    }

    protected function applyRenameColumn(): array
    {
        $this
            ->queryExecutor
            ->executeStatement(
                $this->writeRenameColumn(),
            )
        ;
    }

    protected function applyRenameTable(): array
    {
        
    }
}
