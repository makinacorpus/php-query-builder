<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff;

interface ChangeLogItem
{
    /**
     * Get database this change acts upon.
     */
    public function getDatabase(): string;

    /**
     * Get schema this change acts upon.
     */
    public function getSchema(): string;
}
