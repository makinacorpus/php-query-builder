<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Result;

use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Error\ResultError;

class DefaultResultRow implements ResultRow
{
    private array $names = [];
    private ?array $lowered = null;

    public function __construct(
        private array $rawValues,
        private ?Converter $converter = null,
    ) {
        $this->names = \array_keys($rawValues);
    }

    #[\Override]
    public function get(int|string $columnName, ?string $phpType = null, bool $caseSensitive = false): mixed
    {
        $value = $this->rawValues[$this->getColumnName($columnName, $caseSensitive)];

        if (null === $value) {
            return null;
        }

        if ($phpType) {
            if (!$this->converter) {
                $this->converter = new Converter();
            }
            $value = $this->converter->fromSql($phpType, $value);
        }

        return $value;
    }

    #[\Override]
    public function has(int|string $columnName, bool $allowNull = true, bool $caseSensitive = false): bool
    {
        try {
            $name = $this->getColumnName($columnName);

            return $allowNull ? true : isset($this->rawValues[$name]);
        } catch (ResultError $e) {
            return false;
        }
    }

    #[\Override]
    public function raw(int|string $columnName, bool $caseSensitive = false): null|int|float|string
    {
        return $this->rawValues[$this->getColumnName($columnName)];
    }

    #[\Override]
    public function toArray(): array
    {
        return $this->rawValues;
    }

    /**
     * Get column name..
     */
    protected function getColumnName(int|string $name = 0, bool $caseSensitive = false): mixed
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
            if (!$caseSensitive) {
                if (null === $this->lowered) {
                    $this->lowered = [];
                    foreach ($this->names as $key) {
                        if (\is_string($key)) {
                            $this->lowered[\strtolower($key)] = $key;
                        }
                    }
                }

                if ($casedName = ($this->lowered[\strtolower($name)] ?? null)) {
                    return $casedName;
                }
            }

            throw new ResultError(\sprintf("Column with name '%s' does not exist in result.", $name));
        }
        return $name;
    }

    #[\Override]
    public function offsetGet($offset): mixed
    {
        @\trigger_error(\sprintf("You should not use %s instance as array, this is deprecated and will be removed in next major.", ResultRow::class), E_USER_DEPRECATED);

        return $this->get($offset);
    }

    #[\Override]
    public function offsetExists($offset): bool
    {
        @\trigger_error(\sprintf("You should not use %s instance as array, this is deprecated and will be removed in next major.", ResultRow::class), E_USER_DEPRECATED);

        return $this->has($offset);
    }

    #[\Override]
    public function offsetUnset($offset): void
    {
        throw new ResultError("Result rows are immutable.");
    }

    #[\Override]
    public function offsetSet($offset, $value): void
    {
        throw new ResultError("Result rows are immutable.");
    }
}
