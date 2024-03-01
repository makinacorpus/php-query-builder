<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\Diff\AbstractChange;

/**
 * Add a FOREIGN KEY constraint on a table.
 *
 * This code is generated using bin/generate_changes.php.
 *
 * It includes some manually written code, please review changes and apply
 * manual code after each regeneration.
 *
 * @see \MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template\Generator
 * @see bin/generate_changes.php
 */
class ForeignKeyAdd extends AbstractChange
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
        private readonly null|string $name = null,
        /** @var string */
        private readonly null|string $foreignSchema = null,
        /** @var string */
        private readonly string $onDelete = ForeignKeyAdd::ON_DELETE_NO_ACTION,
        /** @var string */
        private readonly string $onUpdate = ForeignKeyAdd::ON_UPDATE_NO_ACTION,
        /** @var bool */
        private readonly bool $deferrable = true,
        /** @var string */
        private readonly string $initially = ForeignKeyAdd::INITIALLY_DEFERRED,
    ) {
        parent::__construct(
            database: $database,
            schema: $schema,
        );
    }

    /** @return string */
    public function getTable(): string
    {
        return $this->table;
    }

    /** @return string */
    public function getName(): null|string
    {
        return $this->name;
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

    /** @return string */
    public function getOnDelete(): string
    {
        return $this->onDelete;
    }

    /** @return string */
    public function getOnUpdate(): string
    {
        return $this->onUpdate;
    }

    /** @return bool */
    public function isDeferrable(): bool
    {
        return $this->deferrable;
    }

    /** @return string */
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
