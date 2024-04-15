<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Modify an arbitrary constraint on a table.
 */
class ConstraintModify extends AbstractChange
{
    public const INITIALLY_DEFERRED = 'deferred';
    public const INITIALLY_IMMEDIATE = 'immediate';

    public function __construct(
        string $schema,
        private readonly string $table,
        private readonly string $name,
        private readonly bool $deferrable = true,
        private readonly string $initially = ConstraintModify::INITIALLY_DEFERRED,
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

    public function isDeferrable(): bool
    {
        return $this->deferrable;
    }

    public function getInitially(): string
    {
        return $this->initially;
    }
}
