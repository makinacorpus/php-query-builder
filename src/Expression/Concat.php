<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * String concatenation expression.
 *
 * Standard implementation uses the || operator, some other use the CONCAT()
 * function call instead.
 */
class Concat implements Expression
{
    /** @var Expression[] */
    private array $arguments = [];

    public function __construct(mixed ...$arguments)
    {
        foreach ($arguments as $argument) {
            $this->arguments[] = ExpressionHelper::value($argument);
        }
    }

    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return Type::text();
    }

    /**
     * @return Expression[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function __clone(): void
    {
        foreach ($this->arguments as $index => $argument) {
            $this->arguments[$index] = clone $argument;
        }
    }
}
