<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\Diff\AbstractChange;

/**
 * Modify an arbitrary constraint on a table.
 *
 * This code is generated using bin/generate_changes.php.
 *
 * It includes some manually written code, please review changes and apply
 * manual code after each regeneration.
 *
 * @see \MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template\Generator
 * @see bin/generate_changes.php
 */
class ConstraintModify extends AbstractChange
{
    const INITIALLY_DEFERRED = 'deferred';
    const INITIALLY_IMMEDIATE = 'immediate';

    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $table,
        /** @var string */
        private readonly string $name,
        /** @var bool */
        private readonly bool $deferrable = true,
        /** @var string */
        private readonly string $initially = ConstraintModify::INITIALLY_DEFERRED,
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
    public function getName(): string
    {
        return $this->name;
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
}
