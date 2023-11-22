<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;

/**
 * Represent a function call.
 */
class StringHash implements Expression
{
    private Expression $value;

    public function __construct(
        mixed $value,
        private string $algo,
    ) {
        $this->value = \is_string($value) ? new Value($value, 'text') : ExpressionHelper::value($value);
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
        return 'text';
    }

    public function getAlgo(): string
    {
        return $this->algo;
    }

    /**
     * @return Expression[]
     */
    public function getValue(): Expression
    {
        return $this->value;
    }

    public function __clone(): void
    {
        $this->value = clone $this->value;
    }
}
