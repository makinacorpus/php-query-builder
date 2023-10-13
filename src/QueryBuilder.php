<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Driver\DriverAwareTrait;
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
    use DriverAwareTrait;

    /**
     * Generate proper SQL with parameter placeholder from any expression.
     */
    public function generate(mixed $expression, mixed $arguments = null): SqlString
    {
        return $this->getWriter()->prepare(ExpressionHelper::raw($expression, $arguments));
    }

    /**
     * {@inheritdoc}
     */
    public function select(null|string|Expression $table = null, ?string $alias = null): Select
    {
        $ret = new Select($table, $alias);

        if ($this->driver) {
            $ret->setDriver($this->driver);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string|Expression $table, ?string $alias = null): Update
    {
        $ret = new Update($table, $alias);

        if ($this->driver) {
            $ret->setDriver($this->driver);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function insert(string|Expression $table): Insert
    {
        $ret = new Insert($table);

        if ($this->driver) {
            $ret->setDriver($this->driver);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(string|Expression $table): Merge
    {
        $ret = new Merge($table);

        if ($this->driver) {
            $ret->setDriver($this->driver);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string|Expression $table, ?string $alias = null): Delete
    {
        $ret = new Delete($table, $alias);

        if ($this->driver) {
            $ret->setDriver($this->driver);
        }

        return $ret;
    }
}
