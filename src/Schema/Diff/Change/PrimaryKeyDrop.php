<?php

declare (strict_type=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Drop the PRIMARY KEY constraint from a table.
 */
class PrimaryKeyDrop extends Change
{
    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $table,
    ) {
        parent::__construct(database: $database, schema: $schema);
    }

    /** @return string */
    public function getTable(): string
    {
        return $this->table;
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
