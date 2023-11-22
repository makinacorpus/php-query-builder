<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;

/**
 * Represents a expressions comparison.
 */
class Comparison implements Expression
{
    const EQUAL = '=';
    const EXISTS = 'exists';
    const GREATER = '>';
    const GREATER_OR_EQUAL = '>=';
    const IN = 'in';
    const IS_NULL = 'is null';
    const LESS = '<';
    const LESS_OR_EQUAL = '<=';
    const NOT_BETWEEN = 'not between';
    const NOT_EQUAL = '<>';
    const NOT_EXISTS = 'not exists';
    const NOT_IN = 'not in';
    const NOT_IS_NULL = 'is not null';

    private ?Expression $left;
    private ?Expression $right;
    private ?string $operator;

    public function __construct(?Expression $left, ?Expression $right, ?string $operator)
    {
        $this->left = $left;
        $this->right = $right;
        $this->operator = $operator;
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
        return 'bool';
    }

    public function getLeft(): ?Expression
    {
        return $this->left;
    }


    public function getRight(): ?Expression
    {
        return $this->right;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }
}
