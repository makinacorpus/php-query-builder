<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff;

/**
 * Changes on the schema.
 */
abstract class AbstractChange
{
    public function __construct(
        private readonly string $database,
        private readonly string $schema,
    ) {}

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getSchema(): string
    {
        return $this->schema;
    }
}
