<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * LPAD() expression.
 */
class Lpad implements Expression
{
    private Expression $value;
    private Expression $fill;
    private ?string $fillString = null;
    private Expression $size;
    private ?int $sizeInt = null;

    /**
     * @param mixed $value
     *   Expression that returns text or a string value.
     * @param mixed $size
     *   Pad size.
     * @param mixed $fill
     *   Expression to fill with, will be space by default.
     */
    public function __construct(
        mixed $value,
        mixed $size,
        mixed $fill = null,
    ) {
        $this->value = \is_string($value) ? new Value($value, Type::varchar()) : ExpressionHelper::value($value);
        if (\is_int($size)) {
            $this->size = new Value($size, Type::int());
            $this->sizeInt = $size;
        } else {
            $this->size = ExpressionHelper::value($size);
        }
        if (\is_string($fill)) {
            $this->fill = new Value($fill, Type::varchar());
            $this->fillString = $fill;
        } else {
            $this->fill = $fill ? ExpressionHelper::value($fill) : new Value(' ',  Type::varchar());
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
        return Type::text();
    }

    /**
     * Get value type if specified.
     */
    public function getValue(): Expression
    {
        return $this->value;
    }

    /**
     * Get integer value of size if known from the start in order to generate
     * a more efficient variant.
     */
    public function getFillString(): ?string
    {
        return $this->fillString;
    }

    /**
     * Get value type if specified.
     */
    public function getFill(): Expression
    {
        return $this->fill;
    }

    /**
     * Get integer value of size if known from the start in order to generate
     * a more efficient variant.
     */
    public function getSizeInt(): ?int
    {
        return $this->sizeInt;
    }

    /**
     * Get pad size.
     */
    public function getSize(): Expression
    {
        return $this->size;
    }

    public function __clone(): void
    {
        $this->value = clone $this->value;
        $this->size = clone $this->size;
        $this->fill = clone $this->fill;
    }
}
