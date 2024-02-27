<?php

declare (strict_type=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Modify an arbitrary constraint on a table.
 */
class ConstraintModify extends Change
{
    const INITIALLY_DEFERRED = 'deferred';
    const INITIALLY_IMMEDIATE = 'immediate';

    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $name,
        /** @var string */
        private readonly string $table,
        /** @var bool */
        private readonly bool $deferrable = true,
        /** @var mixed */
        private readonly mixed $initially = 'deferred',
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

    /** @return bool */
    public function isDeferrable(): bool
    {
        return $this->deferrable;
    }

    /** @return mixed */
    public function getInitially(): mixed
    {
        return $this->initially;
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
