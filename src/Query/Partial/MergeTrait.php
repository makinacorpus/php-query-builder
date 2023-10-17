<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Query\Query;

/**
 * Common data for INSERT ON CONCLIT or MERGE queries.
 */
trait MergeTrait
{
    private int $conflictBehaviour = Query::CONFLICT_UPDATE;
    private array $primaryKey = [];
    private ?string $usingTableAlias = null;

    /**
     * Use this only if your RDBMS supports standard SQL:2003 MERGE query,
     * this sets the USING clause table alias.
     *
     * If you don't set one, one will be generated for you.
     */
    public function setUsingTableAlias(string $alias): static
    {
        $this->usingTableAlias = $alias;

        return $this;
    }

    /**
     * Get using table alias.
     */
    public function getUsingTableAlias(): string
    {
        return $this->usingTableAlias ??= $this->createAliasForName('upsert');
    }

    /**
     * Set merge key (primary key for matching for conflict).
     */
    public function setKey(array $columnNames): static
    {
        foreach ($columnNames as $columnName) {
            if (!\is_string($columnName) || false !== \strpos($columnName, '.')) {
                throw new QueryBuilderError("column names in the primary key of an merge query can only be a column name, without table prefix");
            }
        }

        $this->primaryKey = $columnNames;

        return $this;
    }

    /**
     * Get merge key (primary key for matching for conflict).
     */
    public function getKey(): array
    {
        return $this->primaryKey;
    }

    /**
     * Set manually on conflict behaviour.
     */
    public function onConflict(int $mode): static
    {
        if (Query::CONFLICT_IGNORE !== $mode && Query::CONFLICT_UPDATE !== $mode) {
            throw new QueryBuilderError(\sprintf(
                "ON CONFLICT | WHEN [NOT] MATCHED behaviours must be one of %s::CONFLICT_IGNORE or %s::CONFLICT_UPDATE",
                Query::class, Query::class
            ));
        }

        $this->conflictBehaviour = $mode;

        return $this;
    }

    /**
     * Ignore conflicting rows.
     */
    public function onConflictIgnore(): static
    {
        $this->onConflict(Query::CONFLICT_IGNORE);

        return $this;
    }

    /**
     * Update conflicting rows.
     */
    public function onConflictUpdate(): static
    {
        $this->onConflict(Query::CONFLICT_UPDATE);

        return $this;
    }

    /**
     * Get conflict behaviour.
     */
    public function getConflictBehaviour(): int
    {
        return $this->conflictBehaviour;
    }
}
