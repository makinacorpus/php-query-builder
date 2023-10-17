<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\TableExpression;
use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\Query\Partial\FromClauseTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\ReturningQueryTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\WhereClauseTrait;

/**
 * Represents an DELETE query.
 *
 * Here FROM clause trait represents the USING clause.
 */
class Delete extends AbstractQuery
{
    use ReturningQueryTrait;
    use FromClauseTrait;
    use WhereClauseTrait;

    private TableName $table;

    /**
     * Build a new query.
     *
     * @param string|TableExpression $table
     *   SQL FROM clause table name.
     * @param string $alias
     *   Alias for FROM clause table.
     */
    public function __construct(string|Expression $table, ?string $alias = null)
    {
        $this->table = $this->normalizeStrictTable($table, $alias);
        $this->where = new Where();
    }

    /**
     * Get FROM table.
     */
    public function getTable(): TableName
    {
        return $this->table;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->cloneWith();
        $this->cloneFrom();
        $this->table = clone $this->table;
        $this->where = clone $this->where;
    }
}
