<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Query\Partial\FilterClauseTrait;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Type\Type;
use MakinaCorpus\QueryBuilder\Where;

/**
 * Represent an aggregate SELECT column clause.
 *
 * WINDOW (OVER) clause can be null, because OVER () is valid SQL, we need to
 * differenciate an empty OVER from no OVER at all.
 */
class Aggregate implements Expression
{
    use FilterClauseTrait;

    public function __construct(
        private string $functionName,
        private ?Expression $column = null,
        ?Expression $filter = null,
        private null|Window|Identifier $over = null,
    ) {
        if ($filter) {
            if ($filter instanceof Where) {
                $this->filter = $filter;
            } else {
                $this->filter = new Where();
                $this->filter->with($filter);
            }
        } else {
            $this->filter = new Where();
        }
    }

    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return null;
    }

    /**
     * Get aggregate function name.
     */
    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    /**
     * Get expression in aggregate function call.
     */
    public function getColumn(): ?Expression
    {
        return $this->column;
    }

    /**
     * Get FILTER () clause, as a Where instance.
     */
    public function getFilter(): ?Where
    {
        return $this->filter->isEmpty() ? null : $this->filter;
    }

    /**
     * Get OVER (WINDOW) clause, if any. It can be an identifier if WINDOW
     * is written later in SELECT query.
     */
    public function getOverWindow(): null|Window|Identifier
    {
        return $this->over;
    }

    /**
     * Use an existing WINDOW which is at the FROM level.
     *
     * Eg.:
     *    SELECT
     *       foo() OVER (window_alias)
     *    FROM bar
     *    WINDOW window_alias AS (EXPR)
     */
    public function overWindow(string $alias): static
    {
        $this->over = new Identifier($alias);

        return $this;
    }

    /**
     * Create a window function, and register it in OVER.
     *
     * Eg.:
     *    SELECT
     *       foo() OVER (EXPR)
     *    FROM bar
     */
    public function over(mixed $partitionBy = null, mixed $orderBy = null, int $order = Query::ORDER_ASC, int $null = Query::NULL_IGNORE): static
    {
        $window = new Window();
        if ($partitionBy) {
            $window->partitionBy($partitionBy);
        }
        if ($orderBy) {
            $window->orderBy($orderBy, $order, $null);
        }

        $this->over = $window;

        return $this;
    }

    /**
     * Create a window function, and register it at the FROM level, and
     * return the instance for building it.
     *
     * Eg.:
     *    SELECT
     *       foo() OVER (window_alias)
     *    FROM bar
     *    WINDOW window_alias AS (EXPR)
     */
    public function createOver(): Window
    {
        return $this->over = new Window();
    }

    public function __clone(): void
    {
        $this->filter = clone $this->filter;
        if ($this->column) {
            $this->column = clone $this->column;
        }
        if ($this->over) {
            $this->over = clone $this->over;
        }
    }
}
