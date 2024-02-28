<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;
use MakinaCorpus\QueryBuilder\Schema\Diff\AbstractChange;

/**
 * Create a table.
 *
 * This code is generated using bin/generate_changes.php.
 *
 * It includes some manually written code, please review changes and apply
 * manual code after each regeneration.
 *
 * @see \MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template\Generator
 * @see bin/generate_changes.php
 */
class TableCreate extends AbstractChange
{
    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $name,
        /** @var array<ColumnAdd> */
        private readonly array $columns = [],
        /** @var PrimaryKeyAdd */
        private readonly null|PrimaryKeyAdd $primaryKey = null,
        /** @var array<ForeignKeyAdd> */
        private readonly array $foreignKeys = [],
        /** @var array<UniqueConstraintAdd> */
        private readonly array $uniqueKeys = [],
        /** @var array<IndexCreate> */
        private readonly array $indexes = [],
    ) {
        parent::__construct(database: $database, schema: $schema);
    }

    /** @return string */
    public function getName(): string
    {
        return $this->name;
    }

    /** @return array<ColumnAdd> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /** @return PrimaryKeyAdd */
    public function getPrimaryKey(): null|PrimaryKeyAdd
    {
        return $this->primaryKey;
    }

    /** @return array<ForeignKeyAdd> */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /** @return array<UniqueConstraintAdd> */
    public function getUniqueKeys(): array
    {
        return $this->uniqueKeys;
    }

    /** @return array<IndexCreate> */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    #[\Override]
    public function isCreation(): bool
    {
        return true;
    }

    #[\Override]
    public function isModified(AbstractObject $source): bool
    {
        throw new \Exception("Here should be the manually generated code, please revert it.");
    }
}
