<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\Diff\ChangeLogItem;

/**
 * Changes on the schema.
 */
abstract class AbstractChange implements ChangeLogItem
{
    public function __construct(
        private readonly string $schema,
    ) {}

    #[\Override]
    public function getSchema(): string
    {
        return $this->schema;
    }
}
