<?php

declare (strict_type=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Create a table.
 */
class TableCreate extends Change
{
    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $name,
        /** @var array<array> */
        private readonly array $columns,
    ) {
        parent::__construct(database: $database, schema: $schema);
    }

    /** @return string */
    public function getName(): string
    {
        return $this->name;
    }

    /** @return array<array> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    #[\Override]
    public function isCreation(): bool
    {
        return true;
    }

    #[\Override]
    public function isModified(AbstractObject $source): bool
    {
        throw new \Exception("Implement me");
    }
}
