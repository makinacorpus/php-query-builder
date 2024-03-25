<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Represents a raw value, along with an optional type.
 *
 * Value type may be used for later value conversion and will be propagated
 * to ArgumentBag instance, but will have no impact on formatted SQL string.
 *
 * Value itself can be anything including an Expression instance.
 */
class Value implements Castable
{
    private null|Type $type = null;
    private null|Type $castToType = null;

    public function __construct(
        private mixed $value,
        null|string|Type $type = null,
        null|string|Type $castToType = null,
    ) {
        if (null === $value && !$type) {
            $this->type = Type::null();
        }
        if ($type) {
            $this->type = Type::create($type);
        }
        if ($castToType) {
            $this->castToType = Type::create($castToType);
        }
    }

    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return $this->castToType ?? $this->type;
    }

    #[\Override]
    public function getCastToType(): ?Type
    {
        return $this->castToType;
    }

    /**
     * @internal
     *   For writer usage, in certain circumstances, we are lead to attempt
     *   value type guess prior to format SQL code, case in which we will set
     *   the type here for caching purpose.
     */
    public function setType(null|string|Type $type): static
    {
        $this->type = $type ? Type::create($type) : null;

        return $this;
    }

    /**
     * Get value.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Get value type.
     */
    public function getType(): ?Type
    {
        return $this->type;
    }
}
