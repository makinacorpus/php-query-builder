<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Expression\Not;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Value;

/**
 * Represent a condition set with an operator ("and" or "or").
 *
 * Of course, any other expression can be used to create it.
 */
class Where implements Expression
{
    use WhereBuilder;

    const AND = 'and';
    const OR = 'or';

    private string $operator = self::AND;
    private array $children = [];

    public function __construct(?string $operator = null)
    {
        // Using a string allows users that use super powered SQL servers
        // than can do other than AND and OR using whatever they want as
        // operator.
        if ($operator) {
            $this->operator = \strtolower($operator);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return true;
    }

    /**
     * Is it empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->children);
    }

    /**
     * Get operator.
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Get all conditions.
     */
    public function getConditions(): array
    {
        return $this->children;
    }

    /**
     * {@inheritdoc}
     */
    protected function getInstance(): Where
    {
        return $this;
    }

    /**
     * Append a single expression.
     */
    protected function addExpression(mixed $expression): void
    {
        if (\is_callable($expression)) {
            ($expression)($this);
        } else {
            if (!$expression instanceof Expression) {
                $expression = new Value($expression);
            }
            $this->children[] = $expression;
        }
    }

    /**
     * Append a negated single expression.
     */
    protected function addNotExpression(mixed $expression): void
    {
        if (\is_callable($expression)) {
            ($expression)($this);
        } else {
            if (!$expression instanceof Expression) {
                $expression = new Value($expression);
            }
            $this->children[] = new Not($expression);
        }
    }

    /**
     * Add a single raw SQL expression.
     */
    public function withRaw(string $expression, mixed $arguments = null): static
    {
        $this->addExpression(new Raw($expression, $arguments));

        return $this;
    }

    /**
     * Add one or more expressions into this clause.
     */
    public function with(mixed $expression, mixed ...$expressions): static
    {
        $this->addExpression($expression);
        if ($expressions) {
            \array_walk($expressions, fn ($expression) => $this->addExpression($expression));
        }

        return $this;
    }

    /**
     * Add one or more negated expressions into this clause.
     */
    public function notWith(mixed $expression, mixed ...$expressions): static
    {
        $this->addNotExpression($expression);
        if ($expressions) {
            \array_walk($expressions, fn ($expression) => $this->addNotExpression($expression));
        }

        return $this;
    }

    /**
     * Create a nested where clause with a custom operator.
     */
    public function nested(string $operator, mixed ...$expressions): self
    {
        $nested = new self($operator);
        if ($expressions) {
            $nested->with(...$expressions);
        }

        return $this->children[] = $nested;
    }

    /**
     * Create an OR clause and add expressions into it.
     *
     * @return self
     *   The newly created clause.
     */
    public function or(mixed ...$expressions): self
    {
        return $this->nested(self::OR, ...$expressions);
    }

    /**
     * Create an AND clause and add expressions into it.
     *
     * @return self
     *   The newly created clause.
     */
    public function and(mixed ...$expressions): self
    {
        return $this->nested(self::AND, ...$expressions);
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        foreach ($this->children as $index => $expression) {
            $this->children[$index] = clone $expression;
        }
    }
}
