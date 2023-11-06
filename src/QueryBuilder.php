<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Query\Delete;
use MakinaCorpus\QueryBuilder\Query\Insert;
use MakinaCorpus\QueryBuilder\Query\Merge;
use MakinaCorpus\QueryBuilder\Query\RawQuery;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Query\Update;

/**
 * Builds queries, and allow you to forward them to a driver.
 */
class QueryBuilder
{
    /**
     * Create SELECT query.
     */
    public function select(null|string|Expression $table = null, ?string $alias = null): Select
    {
        return new Select($table, $alias);
    }

    /**
     * Create UPDATE query.
     */
    public function update(string|Expression $table, ?string $alias = null): Update
    {
        return new Update($table, $alias);
    }

    /**
     * Create INSERT query.
     */
    public function insert(string|Expression $table): Insert
    {
        return new Insert($table);
    }

    /**
     * Create MERGE query.
     */
    public function merge(string|Expression $table): Merge
    {
        return new Merge($table);
    }

    /**
     * Create DELETE query.
     */
    public function delete(string|Expression $table, ?string $alias = null): Delete
    {
        return new Delete($table, $alias);
    }

    /**
     * Create raw SQL query.
     */
    public function raw(string $expression = null, mixed $arguments = null, bool $returns = false): RawQuery
    {
        return new RawQuery($expression, $arguments, $returns);
    }
}
