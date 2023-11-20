<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Expression\Comparison;

/**
 * Represents the FILTER part of an aggregate expression.
 *
 * Don't forget to handle FILTER in __clone(), __construct().
 */
trait FilterClauseTrait
{
    private Where $filter;

    /**
     * Get FILTER clause.
     */
    public function getFilter(): Where
    {
        return $this->filter;
    }

    /**
     * Open OR statement with parenthesis.
     *
     * @param callable $callback
     *   First argument of callback is the nested Where instance.
     *
     * @deprecated
     *   Normalize with composite Where interface.
     */
    public function filterOr(callable $callback): static
    {
        $this->filter->or($callback);

        return $this;
    }

    /**
     * Open AND statement with parenthesis.
     *
     * @param callable $callback
     *   First argument of callback is the nested Where instance.
     *
     * @deprecated
     *   Normalize with composite Where interface.
     */
    public function filterAnd(callable $callback): static
    {
        $this->filter->and($callback);

        return $this;
    }

    /**
     * Add a condition in the FILTER clause.
     *
     * Default FILTER clause uses AND predicate.
     */
    public function filter(mixed $column, mixed $value = null, string $operator = Comparison::EQUAL): static
    {
        $this->filter->compare($column, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary statement to the FILTER clause.
     *
     * Default FILTER clause uses AND predicate.
     */
    public function filterRaw(mixed $expression, mixed $arguments = null): static
    {
        $this->filter->raw($expression, $arguments);

        return $this;
    }
}
