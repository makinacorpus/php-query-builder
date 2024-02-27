<?php

declare (strict_type=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Rename an arbitrary constraint.
 */
class ConstraintRename extends Change
{
    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $name,
        /** @var string */
        private readonly string $table,
        /** @var string */
        private readonly string $newName,
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
