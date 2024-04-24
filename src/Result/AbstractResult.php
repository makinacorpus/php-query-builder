<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Result;

use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
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
    private ?array $columnTypes = []; // @phpstan-ignore-line
    private mixed $hydrator = null;
    private bool $hydratorUsesArray = false;
    private bool $iterationFreed = false;
    private bool $iterationStarted = false;
    private bool $iterationCompleted = false;
    private bool $rewindable = false; // @phpstan-ignore-line
    private ?Converter $converter = null;

    public function __construct(
        private bool $countIsReliable = true,
    ) {}

    /**
     * Set converter.
     *
     * @internal
     *   For bridge only.
     */
    public function setConverter(Converter $converter): void
    {
        $this->converter = $converter;
    }

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

    #[\Override]
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

    #[\Override]
    public function setRewindable(bool $rewindable = true): static
    {
        $this->dieIfStarted();

        $this->rewindable = $rewindable;

        return $this;
    }

    #[\Override]
    public function isCountReliable(): bool
    {
        return $this->countIsReliable;
    }

    #[\Override]
    public function setHydrator(callable $hydrator): static
    {
        $this->dieIfStarted();

        $this->hydrator = $this->prepareHydrator($hydrator);

        return $this;
    }

    #[\Override]
    public function setColumnType(int|string $columnName, string $phpType): static
    {
        $this->dieIfStarted();

        if (\is_int($columnName)) {
            throw new ResultError("Not implemented yet.");
        }

        $this->columnTypes[$columnName] = $phpType;

        return $this;
    }

    #[\Override]
    public function setColumnTypes(array $columnTypes): static
    {
        foreach ($columnTypes as $columnName => $phpType) {
            $this->setColumnType($columnName, $phpType);
        }

        return $this;
    }

    #[\Override]
    public function fetchRow(): ?ResultRow
    {
        if ($row = $this->fetchNext()) {
            return new DefaultResultRow($row, $this->converter);
        }
        return null;
    }

    #[\Override]
    public function fetchHydrated(): mixed
    {
        if (null === $this->hydrator) {
            throw new ResultError("Hydrator is not set.");
        }

        $row = $this->fetchNext();

        if (null === $row) {
            return null;
        }

        return ($this->hydrator)($this->hydratorUsesArray ? $row : new DefaultResultRow($row, $this->converter));
    }

    #[\Override]
    public function fetchAllHydrated(): array
    {
        $this->dieIfStarted();

        $ret = [];
        while ($row = $this->fetchHydrated()) {
            $ret[] = $row;
        }

        return $ret;
    }

    /**
     * @deprecated
     */
    #[\Override]
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

        throw new QueryBuilderError('Only fetch modes declared on Doctrine\DBAL\FetchMode are supported by legacy API.');
    }

    #[\Override]
    public function fetchNumeric(): null|array
    {
        if ($row = $this->fetchAssociative()) {
            return \array_values($row);
        }
        return null;
    }

    #[\Override]
    public function fetchAssociative(): null|array
    {
        return $this->fetchNext();
    }

    #[\Override]
    public function fetchOne(int|string $valueColumn = 0): mixed
    {
        return $this->getColumnValue($this->fetchNext(), $valueColumn);
    }

    #[\Override]
    public function fetchAllNumeric(): array
    {
        $this->dieIfStarted();

        $ret = [];
        while ($row = $this->fetchNext()) {
            $ret[] = \array_values($row);
        }

        return $ret;
    }

    #[\Override]
    public function fetchAllAssociative(): array
    {
        $this->dieIfStarted();

        $ret = [];
        while ($row = $this->fetchNext()) {
            $ret[] = $row;
        }

        return $ret;
    }

    #[\Override]
    public function fetchAllKeyValue(int|string $keyColumn = 0, int|string $valueColumn = 1): array
    {
        $this->dieIfStarted();

        $ret = [];
        while ($row = $this->fetchNext()) {
            $ret[$this->getColumnValue($row, $keyColumn)] = $this->getColumnValue($row, $valueColumn);
        }

        return $ret;
    }

    #[\Override]
    public function fetchAllAssociativeIndexed(int|string $keyColumn = 0): array
    {
        $this->dieIfStarted();

        $ret = [];
        while ($row = $this->fetchNext()) {
            $ret[$this->getColumnValue($row, $keyColumn)] = $row;
        }

        return $ret;
    }

    #[\Override]
    public function fetchFirstColumn(int|string $valueColumn = 0): array
    {
        $this->dieIfStarted();

        $ret = [];
        while ($row = $this->fetchNext()) {
            $ret[] = $this->getColumnValue($row, $valueColumn);
        }

        return $ret;
    }

    #[\Override]
    public function iterateNumeric(): \Traversable
    {
        $this->dieIfStarted();

        return (function () {
            while ($row = $this->fetchNext()) {
                yield \array_values($row);
            }
        })();
    }

    #[\Override]
    public function iterateAssociative(): \Traversable
    {
        $this->dieIfStarted();

        return (function () {
            while ($row = $this->fetchNext()) {
                yield $row;
            }
        })();
    }

    #[\Override]
    public function iterateKeyValue(int|string $keyColumn = 0, int|string $valueColumn = 1): \Traversable
    {
        $this->dieIfStarted();

        return (function () use ($keyColumn, $valueColumn) {
            while ($row = $this->fetchNext()) {
                yield $this->getColumnValue($row, $keyColumn) => $this->getColumnValue($row, $valueColumn);
            }
        })();
    }

    #[\Override]
    public function iterateAssociativeIndexed(int|string $keyColumn = 0): \Traversable
    {
        $this->dieIfStarted();

        return (function () use ($keyColumn) {
            while ($row = $this->fetchNext()) {
                yield $this->getColumnValue($row, $keyColumn) => $row;
            }
        })();
    }

    #[\Override]
    public function iterateColumn(int|string $valueColumn = 0): \Traversable
    {
        $this->dieIfStarted();

        return (function () use ($valueColumn) {
            while ($row = $this->fetchNext()) {
                yield $this->getColumnValue($row, $valueColumn);
            }
        })();
    }

    #[\Override]
    public function rowCount(): int
    {
        $this->dieIfFreed();

        return $this->rowCount ??= $this->doRowCount();
    }

    #[\Override]
    public function columnCount(): int
    {
        $this->dieIfFreed();

        return $this->columnCount ??= $this->doColumnCount();
    }

    #[\Override]
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
     * Warning: for this method to work with PDO, fetch mode must be set
     * to \PDO::FETCH_ASSOC, other wise columns count and order may vary
     * and cause unpredictable results.
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
