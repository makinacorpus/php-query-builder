<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff;

use MakinaCorpus\QueryBuilder\Schema\SchemaManager;

class ChangeLog
{
    public function __construct(
        private readonly SchemaManager $schemaManager,
        /** @var AbstractChange[] */
        private array $changes = [],
    ) {}

    public function add(AbstractChange $change): void
    {
        $this->changes[] = $change;
    }

    public function getAll(): iterable
    {
        return $this->changes;
    }

    public function diff(): ChangeLog
    {
        foreach ($this->changes as $change) {
            // load target object
            // run has modified
        }

        // @todo fix this
        return $this;
    }
}
