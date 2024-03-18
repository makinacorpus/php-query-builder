<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Type\Type;

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

    #[\Override]
    public function returns(): bool
    {
        return $this->expression->returns();
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return $this->expression->returnType();
    }

    /**
     * Get decorated expression.
     */
    public function getExpression(): Expression
    {
        return $this->expression;
    }

    #[\Override]
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    #[\Override]
    public function cloneWithAlias(?string $alias): static
    {
        $ret = clone $this;
        $ret->alias = $alias;

        return $ret;
    }
}
