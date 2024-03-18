<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Negate a boolean expression.
 */
class Not implements Expression
{
    public function __construct(
        private Expression $expression,
    ) {}

    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return Type::bool();
    }

    public function getExpression(): Expression
    {
        return $this->expression;
    }
}
