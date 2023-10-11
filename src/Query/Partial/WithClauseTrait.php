<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\ExpressionHelper;

/**
 * Represents the WITH part of any query.
 */
trait WithClauseTrait
{
    /** @var WithStatement[] */
    private array $with = [];

    /**
     * Get WITH clauses array.
     *
     * @return array
     */
    final public function getAllWith(): array
    {
        return $this->with;
    }

    /**
     * Add with statement.
     */
    final public function with(string $alias, mixed $select, bool $isRecursive = false): static
    {
        $this->with[] = new WithStatement($alias, ExpressionHelper::table($select), $isRecursive);

        return $this;
    }

    /**
     * Create new with statement.
     */
    final public function createWith(string $alias, mixed $table, bool $isRecursive = false): Select
    {
        $select = new Select($table);
        $this->with[] = new WithStatement($alias, $select, $isRecursive);

        return $select;
    }

    /**
     * Deep clone support.
     */
    protected function cloneWith()
    {
        foreach ($this->with as $index => $with) {
            $this->with[$index] = clone $with;
        }
    }
}
