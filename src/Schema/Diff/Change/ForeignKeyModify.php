<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;
use MakinaCorpus\QueryBuilder\Schema\Diff\AbstractChange;

/**
 * Modify a FOREIGN KEY constraint on a table.
 *
 * This code is generated using bin/generate_changes.php.
 *
 * It includes some manually written code, please review changes and apply
 * manual code after each regeneration.
 *
 * @see \MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template\Generator
 * @see bin/generate_changes.php
 */
class ForeignKeyModify extends AbstractChange
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
        /** @var string */
        private readonly string $onDelete = ForeignKeyModify::ON_DELETE_NO_ACTION,
        /** @var string */
        private readonly string $onUpdate = ForeignKeyModify::ON_UPDATE_NO_ACTION,
        /** @var bool */
        private readonly bool $deferrable = true,
        /** @var string */
        private readonly string $initially = ForeignKeyModify::INITIALLY_DEFERRED,
    ) {
        parent::__construct(database: $database, schema: $schema);
    }

    /** @return string */
    public function getTable(): string
    {
        return $this->table;
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
