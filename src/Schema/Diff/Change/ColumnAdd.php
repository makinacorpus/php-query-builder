<?php

declare (strict_type=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Schema\AbstractObject;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change;

/**
 * Add a COLUMN.
 */
class ColumnAdd extends Change
{
    public function __construct(
        string $database,
        string $schema,
        /** @var string */
        private readonly string $table,
        /** @var string */
        private readonly string $name,
        /** @var string */
        private readonly string $type,
        /** @var bool */
        private readonly bool $nullable,
        /** @var string */
        private readonly null|string $default = null,
    ) {
        parent::__construct(database: $database, schema: $schema);
    }

    /** @return string */
    public function getTable(): string
    {
        return $this->table;
    }

    /** @return string */
    public function getName(): string
    {
        return $this->name;
    }

    /** @return string */
    public function getType(): string
    {
        return $this->type;
    }

    /** @return bool */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /** @return string */
    public function getDefault(): null|string
    {
        return $this->default;
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
