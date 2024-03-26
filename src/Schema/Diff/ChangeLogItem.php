<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff;

interface ChangeLogItem
{
    /**
     * Get schema this change acts upon.
     */
    public function getSchema(): string;
}
