<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Renames a table.
 */
class TableRename extends AbstractChange
{
    public function __construct(
        string $database,
        string $schema,
        private readonly string $name,
        private readonly string $newName,
    ) {
        parent::__construct(database: $database, schema: $schema);
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
