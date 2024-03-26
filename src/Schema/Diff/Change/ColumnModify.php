<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Change;

use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Add a COLUMN.
 */
class ColumnModify extends AbstractChange
{
    private readonly ?Type $type;

    public function __construct(
        string $schema,
        private readonly string $table,
        private readonly string $name,
        null|string|Type $type = null,
        private readonly ?bool $nullable = null,
        private readonly ?string $default = null,
        private readonly bool $dropDefault = false,
        private readonly ?string $collation = null,
    ) {
        parent::__construct(schema: $schema);

        $this->type = $type ? Type::create($type) : null;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function isNullable(): ?bool
    {
        return $this->nullable;
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }

    public function isDropDefault(): bool
    {
        return $this->dropDefault;
    }

    public function getCollation(): ?string
    {
        return $this->collation;
    }
}
