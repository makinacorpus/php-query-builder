<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;

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

    /**
     * Is this change an object creation.
     */
    public abstract function isCreation(): bool;

    /**
     * Check if current change actually changes the schema.
     */
    public abstract function isModified(AbstractObject $source): bool;
}
