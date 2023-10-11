<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Query\Query;

final class JoinStatement
{
    private Expression $table;
    private Where $condition;
    private string $mode = Query::JOIN_INNER;

    public function __construct(
        mixed $table,
        mixed $condition = null,
        string $mode = Query::JOIN_INNER
    ) {
        $this->table = ExpressionHelper::table($table);
        $this->condition = ExpressionHelper::where($condition);
        $this->mode = $mode;
    }

    public function getTable(): Expression
    {
        return $this->table;
    }

    public function getCondition(): Where
    {
        return $this->condition;
    }

    public function getJoinMode(): string
    {
        return $this->mode;
    }

    public function __clone()
    {
        $this->table = clone $this->table;
        $this->condition = clone $this->condition;
    }
}
