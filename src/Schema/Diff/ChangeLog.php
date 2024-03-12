<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff;

class ChangeLog
{
    public function __construct(
        /** @var ChangeLogItem[] */
        private array $changes = [],
    ) {}

    public function add(ChangeLogItem $change): void
    {
        $this->changes[] = $change;
    }

    /**
     * @return ChangeLogItem[]
     */
    public function getAll(): iterable
    {
        return $this->changes;
    }
}
