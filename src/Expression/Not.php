<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;

/**
 * Negate a boolean expression.
 */
class Not implements Expression
{
    public function __construct(
        private Expression $expression,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return true;
    }

    public function getExpression(): Expression
    {
        return $this->expression;
    }
}
