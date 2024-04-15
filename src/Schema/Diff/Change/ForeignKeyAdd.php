<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Add a FOREIGN KEY constraint on a table.
 */
class ForeignKeyAdd extends AbstractChange
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
        /** @var array<string> */
        private readonly array $columns,
        private readonly string $foreignTable,
        /** @var array<string> */
        private readonly array $foreignColumns,
        private readonly ?string $name = null,
        private readonly ?string $foreignSchema = null,
        private readonly string $onDelete = ForeignKeyAdd::ON_DELETE_NO_ACTION,
        private readonly string $onUpdate = ForeignKeyAdd::ON_UPDATE_NO_ACTION,
        private readonly bool $deferrable = true,
        private readonly string $initially = ForeignKeyAdd::INITIALLY_DEFERRED,
    ) {
        parent::__construct(schema: $schema);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /** @return array<string> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getForeignTable(): string
    {
        return $this->foreignTable;
    }

    /** @return array<string> */
    public function getForeignColumns(): array
    {
        return $this->foreignColumns;
    }

    public function getForeignSchema(): ?string
    {
        return $this->foreignSchema;
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

    /**
     * Used in edge cases, for example when you CREATE INDEX in MySQL,
     * it requires you to give an index name, but this API doesn't
     * because almost all RDBMS will generate one for you. This is not
     * part of the API, it simply help a very few of those edge cases
     * not breaking.
     */
    public function generateName(): string
    {
        $pieces = [];
        $pieces[] = $this->table;
        $pieces[] = $this->foreignTable;
        $pieces[] = \implode('_', $this->foreignColumns);
        $pieces[] = 'fk';

        return \implode('_', \array_filter($pieces));
    }
}
