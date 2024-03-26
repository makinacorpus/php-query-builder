<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Create a table.
 */
class TableCreate extends AbstractChange
{
    public function __construct(
        string $schema,
        private readonly string $name,
        /** @var array<ColumnAdd> */
        private readonly array $columns = [],
        private readonly ?PrimaryKeyAdd $primaryKey = null,
        /** @var array<ForeignKeyAdd> */
        private readonly array $foreignKeys = [],
        /** @var array<UniqueKeyAdd> */
        private readonly array $uniqueKeys = [],
        /** @var array<IndexCreate> */
        private readonly array $indexes = [],
        private readonly bool $temporary = false,
    ) {
        parent::__construct(schema: $schema);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return array<ColumnAdd> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getPrimaryKey(): ?PrimaryKeyAdd
    {
        return $this->primaryKey;
    }

    /** @return array<ForeignKeyAdd> */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /** @return array<UniqueKeyAdd> */
    public function getUniqueKeys(): array
    {
        return $this->uniqueKeys;
    }

    /** @return array<IndexCreate> */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function isTemporary(): bool
    {
        return $this->temporary;
    }
}
