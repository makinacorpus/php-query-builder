<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Represent a data type name.
 *
 * Mostly used for schema alteration, but we need this to exist as an expression
 * because solely the writer knows about the type converter, for emiting the
 * correct data types to database vendor.
 */
class DataType implements Expression
{
    private Type $type;

    public function __construct(
        string|Type $type,
    ) {
        $this->type = Type::create($type);
    }

    #[\Override]
    public function returns(): bool
    {
        return false;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return null;
    }

    public function getDataType(): Type
    {
        return $this->type;
    }

    public function __clone()
    {
        $this->type = clone $this->type;
    }
}
