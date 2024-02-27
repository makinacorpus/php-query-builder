<?php

declare (strict_type=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Drop an arbitrary constraint from a table.
 */
class ConstraintDrop extends Change
{
    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $name,
        /** @var string */
        private readonly string $table,
    ) {
        parent::__construct(database: $database, schema: $schema);
    }

    /** @return string */
    public function getName(): string
    {
        return $this->name;
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
