<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;
use MakinaCorpus\QueryBuilder\Schema\Diff\AbstractChange;

/**
 * Add a COLUMN.
 *
 * This code is generated using bin/generate_changes.php.
 *
 * It includes some manually written code, please review changes and apply
 * manual code after each regeneration.
 *
 * @see \MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template\Generator
 * @see bin/generate_changes.php
 */
class ColumnModify extends AbstractChange
{
    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $table,
        /** @var string */
        private readonly string $name,
        /** @var string */
        private readonly string $type,
        /** @var bool */
        private readonly bool $nullable,
        /** @var string */
        private readonly null|string $default = null,
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

    /** @return string */
    public function getType(): string
    {
        return $this->type;
    }

    /** @return bool */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /** @return string */
    public function getDefault(): null|string
    {
        return $this->default;
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
