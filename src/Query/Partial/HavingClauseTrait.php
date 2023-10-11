<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Expression\Comparison;

/**
 * Represents the HAVING part of any query.
 *
 * Don't forget to handle HAVING in __clone(), __construct().
 */
trait HavingClauseTrait
{
    private Where $having;

    /**
     * Get HAVING clause.
     */
    public function getHaving(): Where
    {
        return $this->having;
    }

    /**
     * Open OR expression with parenthesis.
     *
     * @param callable $callback
     *   First argument of callback is the nested Where instance.
     *
     * @deprecated
     *   Normalize with composite Where interface.
     */
    public function havingOr(callable $callback): static
    {
        $this->having->or($callback);

        return $this;
    }

    /**
     * Open AND expression with parenthesis.
     *
     * @param callable $callback
     *   First argument of callback is the nested Where instance.
     *
     * @deprecated
     *   Normalize with composite Where interface.
     */
    public function havingAnd(callable $callback): static
    {
        $this->having->and($callback);

        return $this;
    }

    /**
     * Add a condition in the HAVING clause.
     *
     * Default HAVING clause uses AND predicate.
     */
    public function having(mixed $column, mixed $value = null, string $operator = Comparison::EQUAL): static
    {
        $this->having->comparison($column, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary expression to the HAVING clause.
     *
     * Default HAVING clause uses AND predicate.
     */
    public function havingRaw(mixed $expression, mixed $arguments = null): static
    {
        $this->having->raw($expression, $arguments);

        return $this;
    }
}
