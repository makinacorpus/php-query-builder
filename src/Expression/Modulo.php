<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;

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

    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function returnType(): ?string
    {
        // % operator can be used for various other types.
        return $this->left->returnType() ?? 'int';
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
