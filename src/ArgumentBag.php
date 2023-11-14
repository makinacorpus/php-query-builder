<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\Value;

/**
 * Stores a copy of all parameters, and matching type if any found.
 *
 * Parameters are always an ordered array, they may not be identifier from
 * within the query, but they can be in this bag.
 */
final class ArgumentBag
{
    private array $data = [];
    private array $types = [];
    private int $index = 0;
    private ?array $converted = null;
    private Converter $converter;

    public function __construct(?Converter $converter = null)
    {
        $this->converter = $converter ?? new Converter();
    }

    /**
     * Append the given array to this instance
     */
    public function addAll(iterable $array): void
    {
        foreach ($array as $value) {
            $this->add($value);
        }
    }

    /**
     * Add a parameter
     *
     * @param mixed $value
     *   Value.
     * @param ?string $type
     *   SQL datatype.
     *
     * @return int
     *   Added item position for computing value placeholder in SQL string.
     */
    public function add(mixed $value, ?string $type = null): int
    {
        $this->converted = null;

        if ($value instanceof Value) {
            if (!$type) {
                $type = $value->getType();
            }
            $value = $value->getValue();
        } else if ($value instanceof Expression) {
            throw new QueryBuilderError(\sprintf("Value cannot be an %s instance", Expression::class));
        }

        $index = $this->index;
        $this->index++;

        $this->types[$index] = $type;
        $this->data[$index] = $value;

        return $index;
    }

    /**
     * Count items.
     */
    public function count(): int
    {
        return $this->index;
    }

    /**
     * Get all values converted to SQL value strings.
     */
    public function getAll(): array
    {
        if (null === $this->converted) {
            $this->converted = [];

            foreach ($this->data as $index => $value) {
                $this->converted[] = $this->converter->toSql(
                    $value,
                    $this->getTypeAt($index)
                );
            }
        }

        return $this->converted;
    }

    /**
     * Get all raw values as given by the API user.
     */
    public function getAllRaw(): array
    {
        return $this->data;
    }

    /**
     * Get types.
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Set type at index if not set.
     */
    public function setTypeAt(int $index, ?string $type): void
    {
        if (!isset($this->types[$index])) {
            $this->types[$index] = $type;
            // Avoid PHP warnings in certain circumstances.
            if (!\array_key_exists($index, $this->data)) {
                $this->data[$index] = null;
            }
        }
    }

    /**
     * Get datatype for given index.
     */
    public function getTypeAt(int $index): ?string
    {
        return $this->types[$index] ?? null;
    }
}
