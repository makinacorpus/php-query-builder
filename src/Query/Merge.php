<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\Query\Partial\InsertTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\MergeTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\ReturningQueryTrait;

/**
 * Represent either one of UPDATE .. ON CONFLICT DO .. or MERGE .. queries
 * depending upon your database implementation.
 */
class Merge extends AbstractQuery
{
    use InsertTrait;
    use MergeTrait;
    use ReturningQueryTrait;

    private TableName $table;

    /**
     * Build a new query.
     *
     * @param string|Expression $table
     *   SQL INTO clause table name.
     * @param string $alias
     *   Alias for FROM clause table.
     */
    public function __construct(string|Expression $table, ?string $alias = null)
    {
        $this->table = $this->normalizeStrictTable($table, $alias);
    }

    /**
     * Get INTO table.
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
        $this->table = clone $this->table;
        $this->query = clone $this->query;
    }
}
