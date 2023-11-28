<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Result;

use MakinaCorpus\QueryBuilder\Error\ResultError;

class DefaultResultRow implements ResultRow
{
    private array $names = [];

    public function __construct(
        private array $rawValues,
    ) {
        $this->names = \array_keys($rawValues);
    }

    /**
     * {@inheritdoc}
     */
    public function get(int|string $columnName, ?string $phpType = null): mixed
    {
        $value = $this->rawValues[$this->getColumnName($columnName)];

        if (null === $value) {
            return null;
        }

        if ($phpType) {
            throw new \Exception("Type conversion from SQL to PHP is not implemented yet.");
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function has(int|string $columnName, bool $allowNull = true): bool
    {
        try {
            $name = $this->getColumnName($columnName);

            return $allowNull ? true : isset($this->rawValues[$name]);
        } catch (ResultError $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function raw(int|string $columnName): null|int|float|string
    {
        return $this->rawValues[$this->getColumnName($columnName)];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->rawValues;
    }

    /**
     * Get column name..
     */
    protected function getColumnName(int|string $name = 0): mixed
    {
        if (\is_int($name)) {
            if ($name < 0) {
                throw new ResultError(\sprintf("Column with a negative index %d cannot exist in result.", $name));
            }
            if ($name >= \count($this->names)) {
                throw new ResultError(\sprintf("Column with index %d does not exist in result.", $name));
            }
            return $this->names[$name];
        }

        if (!\array_key_exists($name, $this->rawValues)) {
            throw new ResultError(\sprintf("Column with name '%s' does not exist in result.", $name));
        }
        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): mixed
    {
        @\trigger_error(\sprintf("You should not use %s instance as array, this is deprecated and will be removed in next major.", ResultRow::class), E_USER_DEPRECATED);

        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        @\trigger_error(\sprintf("You should not use %s instance as array, this is deprecated and will be removed in next major.", ResultRow::class), E_USER_DEPRECATED);

        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new ResultError("Result rows are immutable.");
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new ResultError("Result rows are immutable.");
    }
}
