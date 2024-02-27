<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff;

use MakinaCorpus\QueryBuilder\Schema\SchemaManager;

class SchemaTransaction
{
    public function __construct(
        private readonly SchemaManager $schemaManager,
        public readonly string $database,
    ) {}

    public function commit(): void
    {
        //
    }
}
