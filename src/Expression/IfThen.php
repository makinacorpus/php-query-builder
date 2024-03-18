<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * IF .. THEN .. ELSE statement.
 *
 * Depending upon SQL dialect, it might be converted to a CASE WHEN .. THEN
 * expression instead.
 */
class IfThen implements Expression
{
    private Expression $condition;
    private Expression $then;
    private Expression $else;

    public function __construct(mixed $condition, mixed $then, mixed $else = null)
    {
        $this->condition = ExpressionHelper::column($condition);
        $this->then = ExpressionHelper::value($then);
        $this->else = null === $else ? new NullValue() : ExpressionHelper::value($else);
    }

    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return null;
    }

    public function getCondition(): Expression
    {
        return $this->condition;
    }

    public function getThen(): Expression
    {
        return $this->then;
    }

    public function getElse(): Expression
    {
        return $this->else;
    }

    public function toCaseWhen(): CaseWhen
    {
        return (new CaseWhen($this->else))->add($this->condition, $this->then);
    }

    public function __clone(): void
    {
        $this->condition = clone $this->condition;
        $this->then = clone $this->then;
        $this->else = clone $this->else;
    }
}
