<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema;

class Column extends AbstractObject
{
    public function __construct(
        string $database,
        string $name,
        string $table,
        ?string $comment,
        string $schema,
        ?string $vendorId,
        array $options,
        private readonly string $valueType,
        private readonly bool $nullabe = true,
        private readonly ?string $collation = null,
        private readonly ?int $length = null,
        private readonly ?int $precision = null,
        private readonly ?int $scale = null,
        private readonly bool $unsigned = false,
    ) {
        parent::__construct(
            comment: $comment,
            database: $database,
            name: $name,
            options: $options,
            namespace: $table,
            schema: $schema,
            type: ObjectId::TYPE_COLUMN,
            vendorId: $vendorId,
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
    public function getValueType(): string
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
     * Get length.
     */
    public function getLength(): ?int
    {
        return $this->length;
    }

    /**
     * Get precision.
     */
    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * Get scale.
     */
    public function getScale(): ?int
    {
        return $this->scale;
    }

    /**
     * Is unsigned.
     */
    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }
}
