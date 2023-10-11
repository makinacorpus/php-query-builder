<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Expression;

final class WithStatement
{
    public function __construct(
        private string $alias,
        private Expression $table,
        private bool $recursive
    ) {}

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getExpression(): Expression
    {
        return $this->table;
    }

    public function isRecursive(): bool
    {
        return $this->recursive;
    }

    public function __clone()
    {
        $this->table = clone $this->table;
    }
}
