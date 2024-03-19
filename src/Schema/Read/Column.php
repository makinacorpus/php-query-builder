<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Read;

use MakinaCorpus\QueryBuilder\Type\Type;

class Column extends AbstractObject
{
    public function __construct(
        string $database,
        string $name,
        string $table,
        ?string $comment,
        string $schema,
        array $options,
        private readonly Type $valueType,
        private readonly bool $nullabe = true,
        private readonly ?string $collation = null,
        private readonly ?string $default = null,
    ) {
        parent::__construct(
            comment: $comment,
            database: $database,
            name: $name,
            options: $options,
            namespace: $table,
            schema: $schema,
            type: ObjectId::TYPE_COLUMN,
        );
    }

    /**
     * Get table name.
     */
    public function getTable(): string
    {
        return $this->getNamespace();
    }

    /**
     * Get value type.
     */
    public function getValueType(): Type
    {
        return $this->valueType;
    }

    /**
     * Is nullable.
     */
    public function isNullable(): bool
    {
        return $this->nullabe;
    }

    /**
     * Get collation.
     */
    public function getCollation(): ?string
    {
        return $this->collation;
    }

    /**
     * Get default.
     */
    public function getDefault(): ?string
    {
        return $this->default;
    }
}
