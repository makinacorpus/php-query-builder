<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Rename an arbitrary constraint.
 */
class ConstraintRename extends AbstractChange
{
    public function __construct(
        string $schema,
        private readonly string $table,
        private readonly string $name,
        private readonly string $newName,
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

    public function getNewName(): string
    {
        return $this->newName;
    }
}
