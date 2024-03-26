<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Query\AbstractQuery;
use MakinaCorpus\QueryBuilder\Query\Delete;
use MakinaCorpus\QueryBuilder\Query\Insert;
use MakinaCorpus\QueryBuilder\Query\Merge;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Query\RawQuery;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Query\Update;

class DefaultQueryBuilder implements QueryBuilder
{
    private function prepareQuery(Query $query): void
    {
        if ($query instanceof AbstractQuery && $this instanceof DatabaseSession) {
            $query->setDatabaseSession($this);
        }
    }

    #[\Override]
    public function select(null|string|Expression $table = null, ?string $alias = null): Select
    {
        $ret = new Select($table, $alias);
        $this->prepareQuery($ret);

        return $ret;
    }

    #[\Override]
    public function update(string|Expression $table, ?string $alias = null): Update
    {
        $ret = new Update($table, $alias);
        $this->prepareQuery($ret);

        return $ret;
    }

    #[\Override]
    public function insert(string|Expression $table): Insert
    {
        $ret = new Insert($table);
        $this->prepareQuery($ret);

        return $ret;
    }

    #[\Override]
    public function merge(string|Expression $table): Merge
    {
        $ret = new Merge($table);
        $this->prepareQuery($ret);

        return $ret;
    }

    #[\Override]
    public function delete(string|Expression $table, ?string $alias = null): Delete
    {
        $ret = new Delete($table, $alias);
        $this->prepareQuery($ret);

        return $ret;
    }

    #[\Override]
    public function raw(string $expression = null, mixed $arguments = null, bool $returns = false): RawQuery
    {
        $ret = new RawQuery($expression, $arguments, $returns);
        $this->prepareQuery($ret);

        return $ret;
    }

    #[\Override]
    public function expression(): ExpressionFactory
    {
        return new ExpressionFactory();
    }
}
