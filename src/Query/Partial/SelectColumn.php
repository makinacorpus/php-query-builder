<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Expression\Identifier;
use MakinaCorpus\QueryBuilder\Expression\Aliased;

/**
 * I do think this is obsolete now.
 */
final class SelectColumn
{
    public function __construct(
        private Expression $expression,
        private ?string $alias
    ) {}

    public function getExpression(): Expression
    {
        return $this->expression;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getNameInSelect(): ?string
    {
        if ($this->alias) {
            return $this->alias;
        }
        if ($this->expression instanceof Identifier) {
            return $this->expression->getName();
        }
        if ($this->expression instanceof Aliased) {
            return $this->expression->getAlias();
        }
        return null;
    }

    public function __clone()
    {
        $this->expression = clone $this->expression;
    }
}
