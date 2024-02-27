<?php

declare (strict_type=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Modify a FOREIGN KEY constraint on a table.
 */
class ForeignKeyModify extends Change
{
    const INITIALLY_DEFERRED = 'deferred';
    const INITIALLY_IMMEDIATE = 'immediate';

    const ON_DELETE_CASCADE = 'cascade';
    const ON_DELETE_NO_ACTION = 'no action';
    const ON_DELETE_RESTRICT = 'restrict';
    const ON_DELETE_SET_DEFAULT = 'set default';
    const ON_DELETE_SET_NULL = 'set null';

    const ON_UPDATE_CASCADE = 'cascade';
    const ON_UPDATE_NO_ACTION = 'no action';
    const ON_UPDATE_RESTRICT = 'restrict';
    const ON_UPDATE_SET_DEFAULT = 'set default';
    const ON_UPDATE_SET_NULL = 'set null';

    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $table,
        /** @var array<string> */
        private readonly array $columns,
        /** @var string */
        private readonly string $foreignTable,
        /** @var array<string> */
        private readonly array $foreignColumns,
        /** @var string */
        private readonly null|string $foreignSchema = null,
        /** @var mixed */
        private readonly mixed $onDelete = 'no action',
        /** @var mixed */
        private readonly mixed $onUpdate = 'no action',
        /** @var bool */
        private readonly bool $deferrable = true,
        /** @var mixed */
        private readonly mixed $initially = 'deferred',
    ) {
        parent::__construct(database: $database, schema: $schema);
    }

    /** @return string */
    public function getTable(): string
    {
        return $this->table;
    }

    /** @return array<string> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /** @return string */
    public function getForeignTable(): string
    {
        return $this->foreignTable;
    }

    /** @return array<string> */
    public function getForeignColumns(): array
    {
        return $this->foreignColumns;
    }

    /** @return string */
    public function getForeignSchema(): null|string
    {
        return $this->foreignSchema;
    }

    /** @return mixed */
    public function getOnDelete(): mixed
    {
        return $this->onDelete;
    }

    /** @return mixed */
    public function getOnUpdate(): mixed
    {
        return $this->onUpdate;
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
