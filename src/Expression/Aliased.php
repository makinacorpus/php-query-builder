<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;

/**
 * Decorate another expression and give it an alias.
 *
 * @codeCoverageIgnore
 */
final class Aliased implements Expression, WithAlias
{
    public function __construct(
        private Expression $expression,
        private ?string $alias = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return $this->expression->returns();
    }

    /**
     * Get decorated expression.
     */
    public function getExpression(): Expression
    {
        return $this->expression;
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function cloneWithAlias(?string $alias): static
    {
        $ret = clone $this;
        $ret->alias = $alias;

        return $ret;
    }
}
