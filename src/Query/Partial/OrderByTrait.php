<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Expression\Random;
use MakinaCorpus\QueryBuilder\Query\Query;

/**
 * Order by deduplication logic.
 *
 * This is used in SELECT query and WINDOW function.
 */
trait OrderByTrait
{
    /** @var OrderByStatement[] */
    private array $orders = [];

    /**
     * Get order by clauses array.
     */
    public function getAllOrderBy(): array
    {
        return $this->orders;
    }

    /**
     * Add an order by clause.
     *
     * @param string|Expression $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement.
     * @param int $order
     *   One of the Query::ORDER_* constants.
     * @param int $null
     *   Null behavior, nulls first, nulls last, or leave the backend default.
     */
    public function orderBy(mixed $column, int $order = Query::ORDER_ASC, int $null = Query::NULL_IGNORE): static
    {
        $this->orders[] = new OrderByStatement($column, $order, $null);

        return $this;
    }

    /**
     * Add an order by random clause.
     *
     * @param int $order
     *   One of the Query::ORDER_* constants.
     * @param int $null
     *   Null behavior, nulls first, nulls last, or leave the backend default.
     */
    public function orderByRandom(int $order = Query::ORDER_ASC, int $null = Query::NULL_IGNORE): static
    {
        $this->orders[] = new OrderByStatement(new Random(), $order, $null);

        return $this;
    }

    /**
     * Add an order by clause as a raw SQL expression.
     *
     * @param string|Expression $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement.
     * @param int $order
     *   One of the Query::ORDER_* constants.
     * @param int $null
     *   Null behavior, nulls first, nulls last, or leave the backend default.
     */
    public function orderByRaw(mixed $column, int $order = Query::ORDER_ASC, int $null = Query::NULL_IGNORE): static
    {
        $this->orders[] = new OrderByStatement(ExpressionHelper::raw($column), $order, $null);

        return $this;
    }

    /**
     * Deep clone support.
     */
    protected function cloneOrder()
    {
        foreach ($this->orders as $index => $order) {
            $this->orders[$index] = clone $order;
        }
    }
}
