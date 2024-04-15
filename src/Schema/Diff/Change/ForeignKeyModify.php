<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Modify a FOREIGN KEY constraint on a table.
 */
class ForeignKeyModify extends AbstractChange
{
    public const INITIALLY_DEFERRED = 'deferred';
    public const INITIALLY_IMMEDIATE = 'immediate';

    public const ON_DELETE_CASCADE = 'cascade';
    public const ON_DELETE_NO_ACTION = 'no action';
    public const ON_DELETE_RESTRICT = 'restrict';
    public const ON_DELETE_SET_DEFAULT = 'set default';
    public const ON_DELETE_SET_NULL = 'set null';

    public const ON_UPDATE_CASCADE = 'cascade';
    public const ON_UPDATE_NO_ACTION = 'no action';
    public const ON_UPDATE_RESTRICT = 'restrict';
    public const ON_UPDATE_SET_DEFAULT = 'set default';
    public const ON_UPDATE_SET_NULL = 'set null';

    public function __construct(
        string $schema,
        private readonly string $table,
        private readonly string $name,
        private readonly string $onDelete = ForeignKeyModify::ON_DELETE_NO_ACTION,
        private readonly string $onUpdate = ForeignKeyModify::ON_UPDATE_NO_ACTION,
        private readonly bool $deferrable = true,
        private readonly string $initially = ForeignKeyModify::INITIALLY_DEFERRED,
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

    public function getOnDelete(): string
    {
        return $this->onDelete;
    }

    public function getOnUpdate(): string
    {
        return $this->onUpdate;
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
