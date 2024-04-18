<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Read;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Type\Type;

// @todo IDE bug
\class_exists(Type::class);

class Table extends AbstractObject
{
    private ?\Closure $fetchForeignKeys = null;
    private ?array $foreignKeys = null;
    private ?\Closure $fetchReverseForeignKeys = null;
    private ?array $reverseForeignKeys = null;
    private ?\Closure $fetchIndexes = null;
    private ?array $indexes = null;

    /**
     * @param callable|ForeignKey[] $foreignKeys
     * @param callable|ForeignKey[] $reverseForeignKeys
     */
    public function __construct(
        string $database,
        string $name,
        ?string $comment,
        string $schema,
        array $options,
        private readonly ?Key $primaryKey,
        /** @var Column[] */
        private readonly array $columns,
        null|array|callable $foreignKeys,
        null|array|callable $reverseForeignKeys,
        null|array|callable $indexes,
    ) {
        parent::__construct(
            comment: $comment,
            database: $database,
            name: $name,
            options: $options,
            namespace: null,
            schema: $schema,
            type: ObjectId::TYPE_TABLE,
        );

        if (null !== $foreignKeys) {
            if (\is_callable($foreignKeys)) {
                $this->fetchForeignKeys = $foreignKeys(...);
            } else {
                $this->foreignKeys = $foreignKeys;
            }
        }

        if (null !== $reverseForeignKeys) {
            if (\is_callable($reverseForeignKeys)) {
                $this->fetchReverseForeignKeys = $reverseForeignKeys(...);
            } else {
                $this->reverseForeignKeys = $reverseForeignKeys;
            }
        }

        if (null !== $indexes) {
            if (\is_callable($indexes)) {
                $this->fetchIndexes = $indexes(...);
            } else {
                $this->indexes = $indexes;
            }
        }
    }

    /**
     * Get primary key.
     */
    public function getPrimaryKey(): ?Key
    {
        return $this->primaryKey;
    }

    /**
     * Has primary key.
     */
    public function hasPrimaryKey(): bool
    {
        return !empty($this->primaryKey);
    }

    /**
     * Get foreign keys from this table pointing to another.
     *
     * @return ForeignKey[]
     */
    public function getForeignKeys(): array
    {
        if (null !== $this->foreignKeys) {
            return $this->foreignKeys;
        }

        if ($this->fetchForeignKeys) {
            $this->foreignKeys = ($this->fetchForeignKeys)() ?? [];
            $this->fetchForeignKeys = null;
        }

        return $this->foreignKeys;
    }

    /**
     * Get foreign keys from another tables pointing to this one.
     *
     * @return ForeignKey[]
     */
    public function getReverseForeignKeys(): array
    {
        if (null !== $this->reverseForeignKeys) {
            return $this->reverseForeignKeys;
        }

        if ($this->fetchReverseForeignKeys) {
            $this->reverseForeignKeys = ($this->fetchReverseForeignKeys)() ?? [];
            $this->fetchReverseForeignKeys = null;
        }

        return $this->reverseForeignKeys;
    }

    /**
     * Get foreign keys from another tables pointing to this one.
     *
     * @return Index[]
     */
    public function getIndexes(): array
    {
        if (null !== $this->indexes) {
            return $this->indexes;
        }

        if ($this->fetchIndexes) {
            $this->indexes = ($this->fetchIndexes)() ?? [];
            $this->fetchIndexes = null;
        }

        return $this->indexes;
    }

    /**
     * Get column types.
     *
     * @return array<string,Type>
     *   Keys are column names, values are column value types.
     */
    public function getColumnTypeMap(): array
    {
        $ret = [];
        foreach ($this->columns as $name => $column) {
            \assert($column instanceof Column);
            $ret[$name] = $column->getValueType();
        }
        return $ret;
    }

    /**
     * Get all columns name.
     *
     * @return string[]
     */
    public function getColumnNames(): array
    {
        return \array_map(fn (Column $column) => $column->getName(), $this->columns);
    }

    /**
     * Get all columns.
     *
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get a single column.
     */
    public function getColumn(string $name): Column
    {
        foreach ($this->columns as $column) {
            \assert($column instanceof Column);

            if ($column->getName() === $name) {
                return $column;
            }
        }

        throw new QueryBuilderError(\sprintf("Column '%s' does not exist on table '%s'", $name, $this->toString()));
    }
}
