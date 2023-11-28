<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;

/**
 * @internal
 */
final class OrderByStatement
{
    /* readonly */ public Expression $column;

    public function __construct(
        mixed $column,
        public readonly int $order,
        public readonly int $null,
    ) {
        $this->column = ExpressionHelper::column($column);
    }

    public function __clone()
    {
        $this->column = clone $this->column;
    }
}
