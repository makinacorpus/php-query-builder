<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Represents MODULO (%) arithmetic operator.
 */
class Modulo implements Expression
{
    private ?Expression $left;
    private ?Expression $right;

    public function __construct(Expression $left, Expression $right)
    {
        $this->left = $left;
        $this->right = $right;
    }

    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        // % operator can be used for various other types.
        return $this->left->returnType() ?? Type::int();
    }

    public function getLeft(): ?Expression
    {
        return $this->left;
    }

    public function getRight(): ?Expression
    {
        return $this->right;
    }
}
