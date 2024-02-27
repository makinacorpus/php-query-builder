<?php

declare (strict_type=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Rename an arbitrary constraint.
 */
class ForeignKeyRename extends Change
{
    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $table,
        /** @var array<string> */
        private readonly array $columns,
        /** @var string */
        private readonly string $foreignTable,
        /** @var array<string> */
        private readonly array $foreignColumns,
        /** @var string */
        private readonly string $newName,
        /** @var string */
        private readonly null|string $foreignSchema = null,
    ) {
        parent::__construct(database: $database, schema: $schema);
    }

    /** @return string */
    public function getTable(): string
    {
        return $this->table;
    }

    /** @return array<string> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /** @return string */
    public function getForeignTable(): string
    {
        return $this->foreignTable;
    }

    /** @return array<string> */
    public function getForeignColumns(): array
    {
        return $this->foreignColumns;
    }

    /** @return string */
    public function getForeignSchema(): null|string
    {
        return $this->foreignSchema;
    }

    /** @return string */
    public function getNewName(): string
    {
        return $this->newName;
    }

    #[\Override]
    public function isCreation(): bool
    {
        return false;
    }

    #[\Override]
    public function isModified(AbstractObject $source): bool
    {
        throw new \Exception("Implement me");
    }
}
