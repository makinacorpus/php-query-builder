<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Drop a FOREIGN KEY constraint from a table.
 */
class ForeignKeyDrop extends AbstractChange
{
    public function __construct(
        string $schema,
        private readonly string $table,
        private readonly string $name,
    ) {
        parent::__construct(schema: $schema);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
