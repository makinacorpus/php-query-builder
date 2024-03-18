<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Raw user-given SQL string.
 *
 * SECURITY WARNING: THIS WILL NEVER BE ESCAPED, IN ANY CASES.
 */
class Raw implements Expression
{
    private array $arguments;

    public function __construct(
        private string $expression,
        mixed $arguments = null,
        private bool $returns = true
    ) {
        $this->arguments = ExpressionHelper::arguments($arguments);
    }

    /**
     * This particular implementation will yield arbitrary user given SQL code
     * and we cannot predict if it will return values or not. Per default, it
     * will return true until the user says the opposite.
     */
    #[\Override]
    public function returns(): bool
    {
        return $this->returns;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return null;
    }

    /**
     * Get raw SQL string.
     */
    public function getString(): string
    {
        return $this->expression;
    }

    /**
     * Get arguments.
     *
     * @return mixed[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        foreach ($this->arguments as $index => $value) {
            $this->arguments[$index] = \is_object($value) ? clone $value : $value;
        }
    }
}
