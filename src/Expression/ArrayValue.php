<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Represent a value array, such as ARRAY[1, 2, ...].
 *
 * ARRAY[VALUE, ...] is part of SQL standard, nevertheless not all RDBMS will
 * accept this. It is safe to use with PostgreSQL, but not MySQL for example.
 *
 * This is an arbitrary choice, but per default arrays will always be casted
 * with their corresponding type, as such:
 *
 *    CAST(ARRAY[val1, ...] AS value_type[])
 *
 * If you need to disable CAST() because it causes problems, pass false to
 * the third constructor parameter, or do not specify a type.
 */
class ArrayValue implements Expression
{
    private ?Type $valueType = null;

    /**
     * Create a row expression.
     *
     * @param iterable $values
     *   Can contain pretty much anything, keys will be dropped.
     */
    public function __construct(
        private iterable $values,
        ?string $valueType = null,
        private bool $shouldCast = true
    ) {
        if ($valueType) {
            $this->valueType = Type::create($valueType);
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
        return $this->valueType ? Type::create($this->valueType)->toArray() : null;
    }

    /**
     * Get value type if specified.
     */
    public function getValueType(): ?Type
    {
        return $this->valueType;
    }

    /**
     * Get this row values.
     *
     * @return Expression[]
     */
    public function getValues(): iterable
    {
        foreach ($this->values as $value) {
            yield ExpressionHelper::value($value);
        }
    }

    /**
     * Should ARRAY expression be surrounded by a CAST().
     */
    public function shouldCast(): bool
    {
        return null !== $this->valueType && $this->shouldCast;
    }
}
