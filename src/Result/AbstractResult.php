<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Result;

use MakinaCorpus\QueryBuilder\Error\ResultAlreadyStartedError;
use MakinaCorpus\QueryBuilder\Error\ResultError;
use MakinaCorpus\QueryBuilder\Error\ResultLockedError;

/**
 * Base implementation for Result, you should use this.
 */
abstract class AbstractResult implements Result, \IteratorAggregate
{
    private ?int $rowCount = null;
    private ?int $columnCount = null;
    private ?array $columnNames = null;
    private ?array $columnTypes = [];
    private mixed $hydrator = null;
    private bool $hydratorUsesArray = false;
    private bool $iterationFreed = false;
    private bool $iterationStarted = false;
    private bool $iterationCompleted = false;
    private bool $justRewinded = false;
    private bool $rewindable = false;

    public function __construct(
        private bool $countIsReliable = true,
    ) {}

    /**
     * Get row count from driver.
     */
    abstract protected function doRowCount(): int;

    /**
     * Get column count from driver.
     */
    abstract protected function doColumnCount(): int;

    /**
     * Free result.
     */
    abstract protected function doFree(): void;

    /**
     * Fetch next item as an associative array.
     */
    abstract protected function doFetchNext(): null|array;

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        if (null === $this->hydrator) {
            while ($ret = $this->fetchRow()) {
                yield $ret;
            }
        } else {
            while ($ret = $this->fetchHydrated()) {
                yield $ret;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setRewindable(bool $rewindable = true): static
    {
        $this->dieIfStarted();

        $this->rewindable = $rewindable;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isCountReliable(): bool
    {
        return $this->countIsReliable;
    }

    /**
     * {@inheritdoc}
     */
    public function setHydrator(callable $hydrator): static
    {
        $this->dieIfStarted();

        $this->hydrator = $this->prepareHydrator($hydrator);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setColumnType(int|string $columnName, string $phpType): static
    {
        $this->dieIfStarted();

        if (\is_int($columnName)) {
            throw new ResultError("Not implemented yet.");
        }

        $this->columnTypes[$columnName] = $phpType;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setColumnTypes(array $columnTypes): static
    {
        foreach ($columnTypes as $columnName => $phpType) {
            $this->setColumnType($columnName, $phpType);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchRow(): ?ResultRow
    {
        if ($row = $this->fetchNext()) {
            return new DefaultResultRow($row);
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchHydrated(): mixed
    {
        if (null === $this->hydrator) {
            throw new ResultError("Hydrator is not set.");
        }

        $row = $this->fetchNext();

        if (null === $row) {
            return null;
        }

        return ($this->hydrator)($this->hydratorUsesArray ? $row : new DefaultResultRow($row));
    }

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function fetch(int $mode = self::ASSOCIATIVE): mixed
    {
        if ($mode === self::ASSOCIATIVE) {
            return $this->fetchAssociative();
        }

        if ($mode === self::NUMERIC) {
            return $this->fetchNumeric();
        }

        if ($mode === self::COLUMN) {
            return $this->fetchOne();
        }

        throw new \LogicException('Only fetch modes declared on Doctrine\DBAL\FetchMode are supported by legacy API.');
    }

    /**
     * {@inheritdoc}
     */
    public function fetchNumeric(): null|array
    {
        if ($row = $this->fetchAssociative()) {
            return \array_values($row);
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAssociative(): null|array
    {
        return $this->fetchNext();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne(int|string $valueColumn = 0): mixed
    {
        return $this->getColumnValue($this->fetchNext(), $valueColumn);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllNumeric(): array
    {
        $this->dieIfStarted();

        $ret = [];
        while ($row = $this->fetchNext()) {
            $ret[] = \array_values($row);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllAssociative(): array
    {
        $this->dieIfStarted();

        $ret = [];
        while ($row = $this->fetchNext()) {
            $ret[] = $row;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllKeyValue(int|string $keyColumn = 0, int|string $valueColumn = 1): array
    {
        $this->dieIfStarted();

        $ret = [];
        while ($row = $this->fetchNext()) {
            $ret[$this->getColumnValue($row, $keyColumn)] = $this->getColumnValue($row, $valueColumn);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllAssociativeIndexed(int|string $keyColumn = 0): array
    {
        $this->dieIfStarted();

        $ret = [];
        while ($row = $this->fetchNext()) {
            $ret[$this->getColumnValue($row, $keyColumn)] = $row;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchFirstColumn(int|string $valueColumn = 0): array
    {
        $this->dieIfStarted();

        $ret = [];
        while ($row = $this->fetchNext()) {
            $ret[] = $this->getColumnValue($row, $valueColumn);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function iterateNumeric(): \Traversable
    {
        $this->dieIfStarted();

        return (function () {
            while ($row = $this->fetchNext()) {
                yield \array_values($row);
            }
        })();
    }

    /**
     * {@inheritdoc}
     */
    public function iterateAssociative(): \Traversable
    {
        $this->dieIfStarted();

        return (function () {
            while ($row = $this->fetchNext()) {
                yield $row;
            }
        })();
    }

    /**
     * {@inheritdoc}
     */
    public function iterateKeyValue(int|string $keyColumn = 0, int|string $valueColumn = 1): \Traversable
    {
        $this->dieIfStarted();

        return (function () use ($keyColumn, $valueColumn) {
            while ($row = $this->fetchNext()) {
                yield $this->getColumnValue($row, $keyColumn) => $this->getColumnValue($row, $valueColumn);
            }
        })();
    }

    /**
     * {@inheritdoc}
     */
    public function iterateAssociativeIndexed(int|string $keyColumn = 0): \Traversable
    {
        $this->dieIfStarted();

        return (function () use ($keyColumn) {
            while ($row = $this->fetchNext()) {
                yield $this->getColumnValue($row, $keyColumn) => $row;
            }
        })();
    }

    /**
     * {@inheritdoc}
     */
    public function iterateColumn(int|string $valueColumn = 0): \Traversable
    {
        $this->dieIfStarted();

        return (function () use ($valueColumn) {
            while ($row = $this->fetchNext()) {
                yield $this->getColumnValue($row, $valueColumn);
            }
        })();
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount(): int
    {
        $this->dieIfFreed();

        return $this->rowCount ??= $this->doRowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount(): int
    {
        $this->dieIfFreed();

        return $this->columnCount ??= $this->doColumnCount();
    }

    /**
     * {@inheritdoc}
     */
    public function free(): void
    {
        $this->iterationFreed = true;
        $this->doFree();
    }

    /**
     * Fetch next from driver.
     */
    protected function fetchNext(): null|array
    {
        $this->dieIfFreed();

        $this->iterationStarted = true;

        if ($this->iterationCompleted) {
            return null;
        }

        $ret = $this->doFetchNext();

        if (null === $ret) {
            $this->iterationCompleted = true;
        }

        return $ret;
    }

    /**
     * Get column value in row.
     *
     * @return mixed $value
     *   Whatever is the row value, or null if no value found.
     */
    protected function getColumnValue(?array $row, int|string $name = 0): mixed
    {
        if (null === $row) {
            return null;
        }

        if (\is_int($name)) {
            if ($name < 0) {
                throw new ResultError(\sprintf("Column with a negative index %d cannot exist in result.", $name));
            }
            if ($name >= $this->columnCount()) {
                throw new ResultError(\sprintf("Column with index %d does not exist in result.", $name));
            }
            return \array_values($row)[$name];
        }

        if (!\array_key_exists($name, $row)) {
            throw new ResultError(\sprintf("Column with name '%s' does not exist in result.", $name));
        }
        return $row[$name];
    }

    /**
     * Raise an error if result was freed.
     */
    protected function dieIfFreed(): void
    {
        if ($this->iterationFreed) {
            throw new ResultLockedError("Result was freed.");
        }
    }

    /**
     * Raise an error if result iterated has started.
     */
    protected function dieIfStarted(): void
    {
        if ($this->iterationStarted) {
            throw new ResultAlreadyStartedError("Result iteration has already started.");
        }
        $this->dieIfFreed();
    }

    /**
     * From its parameters, prepare hydrator.
     */
    protected function prepareHydrator(callable $hydrator): callable
    {
        $hydrator = \Closure::fromCallable($hydrator);
        $this->hydratorUsesArray = true;

        $reflection = new \ReflectionFunction($hydrator);
        if ($parameters = $reflection->getParameters()) {
            \assert($parameters[0] instanceof \ReflectionParameter);
            if ($parameters[0]->hasType() && ($type = $parameters[0]->getType())) {
                \assert($type instanceof \ReflectionType);
                if ($type instanceof \ReflectionNamedType) {
                    if ($type->getName() === ResultRow::class) {
                        $this->hydratorUsesArray = false;
                    }
                } else {
                    // We didn't implement union types or such, we could
                    // but I'm not sure it worths it.
                }
            } else {
                // Callback has no type, simply provide the ResultRow.
                $this->hydratorUsesArray = false;
            }
        } else {
            // No parameter is probably an error, but the user probably
            // knowns what's its doing.
            $this->hydratorUsesArray = false;
        }

        return $hydrator;
    }
}
