<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Drop a UNIQUE constraint from a table.
 */
class UniqueKeyDrop extends AbstractChange
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
