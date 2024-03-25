<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Represent a single value row, such as (VAL1, VAL2, ...).
 *
 * Composite types and arbitrary rows are the same thing, in the SQL standard
 * except that composite types can yield a type name in the server side, which
 * is practical for casting, converting, and such.
 *
 * But once formated, as input value as well as a output value, constant rows
 * and composite types yield the same syntax.
 *
 * There are a few differences, for named composite types, you can explicit
 * the name when sending it as an input value, or for casting, but those syntax
 * are far from being supported by various SQL vendor dialects, so for now, this
 * API considers that constant rows composite types are always the same.
 *
 * Using arbitrary rows is SQL standard, using named composite types seems to be
 * a PostgreSQL-only thing. At least I wasn't able to find anything in the
 * standard neither in PostgreSQL documentation about this.
 *
 * This is an arbitrary choice, but per default rows will always be casted
 * with their corresponding type, as such:
 *
 *    CAST(ROW(val1, ...) AS composite_type_name)
 *
 * If you need to disable CAST() simply do not set a type name in constructor.
 *
 * In all case, values to expressions conversion will be done lazily while
 * formatting.
 */
class Row implements Expression
{
    private ?array $preparedValues = null;
    private ?int $columnCount = null;

    /**
     * Create a row expression.
     *
     * @param mixed $values
     *   When given a callback, execute first, and apply the following on
     *   the callback result.
     *   When given an array or iterator, each value is a row value.
     *   When given anything else, it will be a single-value row.
     * @param ?string $compositeTypeName
     *   Composite type name if this is for inserting a named composite type.
     */
    public function __construct(
        private mixed $values,
        private ?string $compositeTypeName = null
    ) {}

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
     * Get column count.
     */
    public function getColumnCount(): int
    {
        if (null !== $this->columnCount) {
            return $this->columnCount;
        }
        if (null !== $this->preparedValues) {
            return $this->columnCount = \count($this->preparedValues);
        }

        $this->getValues();

        return $this->columnCount ?? 0;
    }

    /**
     * Get row values.
     *
     * @return Expression[]
     */
    public function getValues(): iterable
    {
        if (null !== $this->preparedValues) {
            return $this->preparedValues;
        }

        $values = $this->values;

        if (!\is_iterable($values)) {
            if (\is_callable($values)) {
                $values = ($values)();
                if (!\is_iterable($values)) {
                    $values = [$values];
                }
            } else {
                $values = [$values];
            }
        }

        $this->columnCount = 0;

        $this->preparedValues = [];
        foreach ($values as $value) {
            $this->preparedValues[] = $value instanceof Expression ? $value : new Value($value);
            $this->columnCount++;
        }

        return $this->preparedValues;
    }

    /**
     * Get composite type for casting.
     */
    public function getCompositeTypeName(): ?string
    {
        return $this->compositeTypeName;
    }

    /**
     * Should ROW expression be surrounded by a CAST().
     */
    public function shouldCast(): bool
    {
        return null !== $this->compositeTypeName;
    }
}
