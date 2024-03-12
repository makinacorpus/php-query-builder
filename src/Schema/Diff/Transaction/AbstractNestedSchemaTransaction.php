<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Transaction;

use MakinaCorpus\QueryBuilder\Schema\Diff\ChangeLogItem;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\ColumnExists;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\IndexExists;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\TableExists;

/**
 * @internal
 *   Exists because PHP has no genericity.
 */
abstract class AbstractNestedSchemaTransaction extends AbstractSchemaTransaction implements ChangeLogItem
{
    public function __construct(
        string $database,
        string $schema,
        private readonly array $conditions = [],
    ) {
        parent::__construct($database, $schema);
    }

    #[\Override]
    public function getDatabase(): string
    {
        return $this->database;
    }

    #[\Override]
    public function getSchema(): string
    {
        return $this->schema;
    }

    /**
     * If table exists then.
     */
    public function ifTableExists(string $table): DeepNestedSchemaTransaction
    {
        return $this->nestWithCondition(new TableExists($this->database, $this->schema, $table));
    }

    /**
     * If table does not exist then.
     */
    public function ifTableNotExists(string $table): DeepNestedSchemaTransaction
    {
        return $this->nestWithCondition(new TableExists($this->database, $this->schema, $table, true));
    }

    /**
     * If column exists then.
     */
    public function ifColumnExists(string $table, string $column): DeepNestedSchemaTransaction
    {
        return $this->nestWithCondition(new ColumnExists($this->database, $this->schema, $table, $column));
    }

    /**
     * If column does not exist then.
     */
    public function ifColumnNotExists(string $table, string $column): DeepNestedSchemaTransaction
    {
        return $this->nestWithCondition(new ColumnExists($this->database, $this->schema, $table, $column, true));
    }

    /**
     * If index exists then.
     */
    public function ifIndexExists(string $table, array $columns): DeepNestedSchemaTransaction
    {
        return $this->nestWithCondition(new IndexExists($this->database, $this->schema, $table, $columns));
    }

    /**
     * If index does not exist then.
     */
    public function ifIndexNotExists(string $table, array $columns): DeepNestedSchemaTransaction
    {
        return $this->nestWithCondition(new IndexExists($this->database, $this->schema, $table, $columns, true));
    }

    /**
     * Get all conditions. No conditions means always execute.
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }
}
