<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;
use MakinaCorpus\QueryBuilder\Schema\Diff\AbstractChange;

/**
 * Drop a COLUMN.
 *
 * This code is generated using bin/generate_changes.php.
 *
 * It includes some manually written code, please review changes and apply
 * manual code after each regeneration.
 *
 * @see \MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template\Generator
 * @see bin/generate_changes.php
 */
class ColumnDrop extends AbstractChange
{
    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $table,
        /** @var string */
        private readonly string $name,
        /** @var bool */
        private readonly bool $cascade = false,
    ) {
        parent::__construct(database: $database, schema: $schema);
    }

    /** @return string */
    public function getTable(): string
    {
        return $this->table;
    }

    /** @return string */
    public function getName(): string
    {
        return $this->name;
    }

    /** @return bool */
    public function isCascade(): bool
    {
        return $this->cascade;
    }

    #[\Override]
    public function isCreation(): bool
    {
        return false;
    }

    #[\Override]
    public function isModified(AbstractObject $source): bool
    {
        throw new \Exception("Here should be the manually generated code, please revert it.");
    }
}
