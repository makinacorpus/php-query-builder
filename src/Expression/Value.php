<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;

/**
 * Represents a raw value, along with an optional type.
 *
 * Value type may be used for later value conversion and will be propagated
 * to ArgumentBag instance, but will have no impact on formatted SQL string.
 *
 * Value itself can be anything including an Expression instance.
 */
class Value implements Expression
{
    public function __construct(
        private mixed $value,
        private ?string $type = null,
        private ?string $castToType = null,
    ) {
        if (null === $value && !$type) {
            $this->type = 'null';
        }
    }

    #[\Override]
    public function returns(): bool
    {
        return true;
    }

    #[\Override]
    public function returnType(): ?string
    {
        return $this->castToType ?? $this->type;
    }

    #[\Override]
    public function getCastToType(): ?string
    {
        return $this->castToType;
    }

    /**
     * @internal
     *   For writer usage, in certain circumstances, we are lead to attempt
     *   value type guess prior to format SQL code, case in which we will set
     *   the type here for caching purpose.
     */
    public function setType(?string $type): static
    {
        $this->type = $type;

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
    public function getType(): ?string
    {
        return $this->type;
    }
}
