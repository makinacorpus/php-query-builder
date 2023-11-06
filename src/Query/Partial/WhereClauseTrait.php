<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Expression\Comparison;

/**
 * Represents the WHERE part of any query.
 *
 * Don't forget to handle WHERE in __clone(), __construct().
 */
trait WhereClauseTrait
{
    private Where $where;

    /**
     * Get WHERE clause.
     */
    public function getWhere(): Where
    {
        return $this->where;
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
    public function whereOr(callable $callback): static
    {
        $this->where->or($callback);

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
    public function whereAnd(callable $callback): static
    {
        $this->where->and($callback);

        return $this;
    }

    /**
     * Add a condition in the WHERE clause.
     *
     * Default WHERE clause uses AND predicate.
     */
    public function where(mixed $column, mixed $value = null, string $operator = Comparison::EQUAL): static
    {
        $this->where->compare($column, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary statement to the WHERE clause.
     *
     * Default WHERE clause uses AND predicate.
     */
    public function whereRaw(mixed $expression, mixed $arguments = null): static
    {
        $this->where->raw($expression, $arguments);

        return $this;
    }
}
