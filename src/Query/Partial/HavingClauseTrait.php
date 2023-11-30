<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Expression\Comparison;

/**
 * Additional methods for query with HAVING.
 */
trait HavingClauseTrait
{
    /**
     * Get HAVING clause.
     */
    abstract protected function getHavingInstance(): Where;

    /**
     * Add a condition in the HAVING clause.
     *
     * Default HAVING clause uses AND predicate.
     */
    public function having(mixed $column, mixed $value = null, string $operator = Comparison::EQUAL): static
    {
        $this->getHavingInstance()->compare($column, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary expression to the HAVING clause.
     *
     * Default HAVING clause uses AND predicate.
     */
    public function havingRaw(mixed $expression, mixed $arguments = null): static
    {
        $this->getHavingInstance()->raw($expression, $arguments);

        return $this;
    }
}
