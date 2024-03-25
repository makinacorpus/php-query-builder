<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * CASE WHEN .. THEN statement.
 */
class CaseWhen implements Expression
{
    private array $cases = [];
    private Expression $else;

    public function __construct(mixed $else = null)
    {
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

    /**
     * Add a single WHEN .. THEN statement.
     */
    public function add(mixed $when, mixed $then): static
    {
        $this->cases[] = new IfThen($when, $then);

        return $this;
    }

    /** @return IfThen[] */
    public function getCases(): array
    {
        return $this->cases;
    }

    public function getElse(): Expression
    {
        return $this->else;
    }

    public function __clone(): void
    {
        foreach ($this->cases as $index => $case) {
            $this->cases[$index] = clone $case;
        }
        $this->else = clone $this->else;
    }
}
