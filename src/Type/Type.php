<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Type;

/**
 * Represent internally any type, with length or precision and scale.
 */
class Type
{
    public readonly bool $isNumeric;
    public readonly bool $isText;

    public function __construct(
        public InternalType $internal,
        public string $name,
        public ?int $length,
        public ?int $precision,
        public ?int $scale,
        public bool $withTimeZone,
    ) {
        $this->isNumeric = $this->internal->isNumeric();
        $this->isText = $this->internal->isText();
    }

    public function toSqlTypeString(): string
    {
        if ($this->length) {
            return $this->name . '(' . $this->length . ')';
        }
        if ($this->precision && $this->scale) {
            return $this->name . '(' . $this->precision . ',' . $this->scale . ')';
        }
        return $this->name;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->toSqlTypeString();
    }
}
