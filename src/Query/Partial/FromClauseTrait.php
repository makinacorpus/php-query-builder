<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Query\Query;

/**
 * Represents the FROM part of a SELECT or UPDATE query.
 *
 * A FROM clause is (from PostgreSQL, but also a few other RDBMS):
 *
 *   - starts with a table name, with an alias or not,
 *   - possibly a comma-separated list of other table names, which can
 *     have aliases as well,
 *   - a list of JOIN statements, which can be aliased as well.
 *
 * In some edge cases, JOIN statements might be rewritten using FROM
 * then soem WHERE clause conditions, this is equivalent (especially
 * in the case of DELETE and UPDATE queries, depending upon the RDBS
 * platform).
 */
trait FromClauseTrait
{
    use AliasHolderTrait;

    /** @var Expression[] */
    private array $from = [];
    /** @var JoinStatement[] */
    private array $join = [];

    /**
     * Get join clauses array.
     *
     * @return Expression[]
     */
    final public function getAllFrom(): array
    {
        return $this->from;
    }

    /**
     * Add FROM table statement.
     */
    final public function from(mixed $table, ?string $alias = null): static
    {
        $this->from[] = $this->normalizeTable($table, $alias);

        return $this;
    }

    /**
     * Get join clauses array.
     *
     * @return JoinStatement[]
     */
    final public function getAllJoin(): array
    {
        return $this->join;
    }

    /**
     * Add join statement.
     */
    final public function join(mixed $table, mixed $condition = null, ?string $alias = null, ?string $mode = Query::JOIN_INNER): static
    {
        $table = $this->normalizeTable($table, $alias);

        $this->join[] = new JoinStatement($table, $condition, $mode);

        return $this;
    }

    /**
     * Add join statement and return the associated Where.
     */
    final public function joinWhere(string|Expression $table, ?string $alias = null, ?string $mode = Query::JOIN_INNER): Where
    {
        $table = $this->normalizeTable($table, $alias);

        $this->join[] = new JoinStatement($table, $condition = new Where(), $mode);

        return $condition;
    }

    /**
     * Add inner statement.
     */
    final public function innerJoin(string|Expression $table, $condition = null, ?string $alias = null): static
    {
        $this->join($table, $condition, $alias, Query::JOIN_INNER);

        return $this;
    }

    /**
     * Add left outer join statement.
     */
    final public function leftJoin(string|Expression $table, $condition = null, ?string $alias = null): static
    {
        $this->join($table, $condition, $alias, Query::JOIN_LEFT_OUTER);

        return $this;
    }

    /**
     * Add inner statement and return the associated Where.
     */
    final public function innerJoinWhere(string|Expression $table, string $alias = null): Where
    {
        return $this->joinWhere($table, $alias, Query::JOIN_INNER);
    }

    /**
     * Add left outer join statement and return the associated Where.
     */
    final public function leftJoinWhere(string|Expression $table, string $alias = null): Where
    {
        return $this->joinWhere($table, $alias, Query::JOIN_LEFT_OUTER);
    }

    /**
     * Deep clone support.
     */
    protected function cloneFrom()
    {
        foreach ($this->from as $index => $expression) {
            $this->from[$index] = clone $expression;
        }
        foreach ($this->join as $index => $join) {
            $this->join[$index] = clone $join;
        }
    }
}
