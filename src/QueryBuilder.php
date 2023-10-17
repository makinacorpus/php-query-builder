<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Query\Delete;
use MakinaCorpus\QueryBuilder\Query\Insert;
use MakinaCorpus\QueryBuilder\Query\Merge;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Query\Update;

/**
 * Builds queries, and allow you to forward them to a driver.
 */
class QueryBuilder
{
    /**
     * {@inheritdoc}
     */
    public function select(null|string|Expression $table = null, ?string $alias = null): Select
    {
        return new Select($table, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string|Expression $table, ?string $alias = null): Update
    {
        return new Update($table, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function insert(string|Expression $table): Insert
    {
        return new Insert($table);
    }

    /**
     * {@inheritdoc}
     */
    public function merge(string|Expression $table): Merge
    {
        return new Merge($table);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string|Expression $table, ?string $alias = null): Delete
    {
        return new Delete($table, $alias);
    }
}
