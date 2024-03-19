<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Renames a COLUMN.
 */
class ColumnRename extends AbstractChange
{
    public function __construct(
        string $database,
        string $schema,
        private readonly string $table,
        private readonly string $name,
        private readonly string $newName,
    ) {
        parent::__construct(database: $database, schema: $schema);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNewName(): string
    {
        return $this->newName;
    }
}
