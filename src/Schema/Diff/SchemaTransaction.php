<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff;

use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\CallbackCondition;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\ColumnExists;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\IndexExists;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\TableExists;
use MakinaCorpus\QueryBuilder\Schema\Diff\Transaction\AbstractSchemaTransaction;
use MakinaCorpus\QueryBuilder\Schema\Diff\Transaction\NestedSchemaTransaction;
use MakinaCorpus\QueryBuilder\Schema\Diff\Transaction\TableBuilder;

// @todo IDE bug.
\class_exists(QueryBuilder::class);

class SchemaTransaction extends AbstractSchemaTransaction
{
    public function __construct(
        string $schema,
        private readonly \Closure $onCommit,
    ) {
        parent::__construct($schema);
    }

    public function commit(): void
    {
        ($this->onCommit)($this);
    }

    /**
     * Create a table builder.
     */
    public function createTable(string $name): TableBuilder
    {
        return new TableBuilder(parent: $this, name: $name, schema: $this->schema);
    }

    /**
     * Execute a user callback and use its result as a condition.
     *
     * @param (callable(QueryBuilder):bool) $callback
     */
    public function ifCallback(callable $callback): NestedSchemaTransaction
    {
        return $this->nestWithCondition(new CallbackCondition($this->schema, $callback));
    }

    /**
     * If table exists then.
     */
    public function ifTableExists(string $table): NestedSchemaTransaction
    {
        return $this->nestWithCondition(new TableExists($this->schema, $table));
    }

    /**
     * If table does not exist then.
     */
    public function ifTableNotExists(string $table): NestedSchemaTransaction
    {
        return $this->nestWithCondition(new TableExists($this->schema, $table, true));
    }

    /**
     * If column exists then.
     */
    public function ifColumnExists(string $table, string $column): NestedSchemaTransaction
    {
        return $this->nestWithCondition(new ColumnExists($this->schema, $table, $column));
    }

    /**
     * If column does not exist then.
     */
    public function ifColumnNotExists(string $table, string $column): NestedSchemaTransaction
    {
        return $this->nestWithCondition(new ColumnExists($this->schema, $table, $column, true));
    }

    /**
     * If index exists then.
     */
    public function ifIndexExists(string $table, array $columns): NestedSchemaTransaction
    {
        return $this->nestWithCondition(new IndexExists($this->schema, $table, $columns));
    }

    /**
     * If index does not exist then.
     */
    public function ifIndexNotExists(string $table, array $columns): NestedSchemaTransaction
    {
        return $this->nestWithCondition(new IndexExists($this->schema, $table, $columns, true));
    }
}
