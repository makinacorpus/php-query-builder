<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff;

use MakinaCorpus\QueryBuilder\Schema\SchemaManager;

class ChangeLog
{
    public function __construct(
        private readonly SchemaManager $schemaManager,
        /** @var Change[] */
        private array $changes = [],
    ) {}

    public function add(Change $change): void
    {
        $this->changes[] = $change;
    }

    public function diff(): ChangeLog
    {
        foreach ($this->changes as $change) {
            // load target object
            // run has modified
        }
    }
}
