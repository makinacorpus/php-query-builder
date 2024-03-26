<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Drop a COLUMN.
 */
class ColumnDrop extends AbstractChange
{
    public function __construct(
        string $schema,
        private readonly string $table,
        private readonly string $name,
        private readonly bool $cascade = false,
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

    public function isCascade(): bool
    {
        return $this->cascade;
    }
}
