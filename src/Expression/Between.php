<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Represents a between comparison.
 */
class Between implements Expression
{
    public function __construct(
        private Expression $column,
        private Expression $from,
        private Expression $to
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

    public function getColumn(): Expression
    {
        return $this->column;
    }

    public function getFrom(): Expression
    {
        return $this->from;
    }

    public function getTo(): Expression
    {
        return $this->to;
    }
}
