<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\Diff\AbstractChange;

/**
 * Renames a table.
 *
 * This code is generated using bin/generate_changes.php.
 *
 * It includes some manually written code, please review changes and apply
 * manual code after each regeneration.
 *
 * @see \MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template\Generator
 * @see bin/generate_changes.php
 */
class TableRename extends AbstractChange
{
    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $name,
        /** @var string */
        private readonly string $newName,
    ) {
        parent::__construct(
            database: $database,
            schema: $schema,
        );
    }

    /** @return string */
    public function getName(): string
    {
        return $this->name;
    }

    /** @return string */
    public function getNewName(): string
    {
        return $this->newName;
    }
}
