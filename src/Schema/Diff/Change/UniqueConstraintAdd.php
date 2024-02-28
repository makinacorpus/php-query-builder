<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;
use MakinaCorpus\QueryBuilder\Schema\Diff\AbstractChange;

/**
 * Create a UNIQUE constraint on a table.
 *
 * This code is generated using bin/generate_changes.php.
 *
 * It includes some manually written code, please review changes and apply
 * manual code after each regeneration.
 *
 * @see \MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template\Generator
 * @see bin/generate_changes.php
 */
class UniqueConstraintAdd extends AbstractChange
{
    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $table,
        /** @var array<string> */
        private readonly array $columns,
        /** @var string */
        private readonly null|string $name = null,
        /** @var bool */
        private readonly bool $nullsDistinct = false,
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
    public function getName(): null|string
    {
        return $this->name;
    }

    /** @return bool */
    public function isNullsDistinct(): bool
    {
        return $this->nullsDistinct;
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
