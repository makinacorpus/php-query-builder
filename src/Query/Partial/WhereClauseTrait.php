<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Expression\Comparison;

/**
 * Additional methods for query with WHERE.
 */
trait WhereClauseTrait
{
    /**
     * Get WHERE clause.
     */
    abstract protected function getWhereInstance(): Where;

    /**
     * Add a condition in the WHERE clause.
     *
     * Default WHERE clause uses AND predicate.
     */
    public function where(mixed $column, mixed $value = null, string $operator = Comparison::EQUAL): static
    {
        $this->getWhereInstance()->compare($column, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary statement to the WHERE clause.
     *
     * Default WHERE clause uses AND predicate.
     */
    public function whereRaw(mixed $expression, mixed $arguments = null): static
    {
        $this->getWhereInstance()->raw($expression, $arguments);

        return $this;
    }
}
