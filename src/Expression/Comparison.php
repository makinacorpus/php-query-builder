<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Represents a expressions comparison.
 */
class Comparison implements Expression
{
    public const EQUAL = '=';
    public const EXISTS = 'exists';
    public const GREATER = '>';
    public const GREATER_OR_EQUAL = '>=';
    public const IN = 'in';
    public const IS_NULL = 'is null';
    public const LESS = '<';
    public const LESS_OR_EQUAL = '<=';
    public const NOT_BETWEEN = 'not between';
    public const NOT_EQUAL = '<>';
    public const NOT_EXISTS = 'not exists';
    public const NOT_IN = 'not in';
    public const NOT_IS_NULL = 'is not null';

    private ?Expression $left;
    private ?Expression $right;
    private ?string $operator;

    public function __construct(?Expression $left, ?Expression $right, ?string $operator)
    {
        $this->left = $left;
        $this->right = $right;
        $this->operator = $operator;
    }

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
