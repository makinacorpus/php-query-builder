<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Error;

class UnexpectedInputValueTypeError extends ValueConversionError
{
    public static function message(string $expected, mixed $value): string
    {
        return \sprintf("Unexpected value type, expected %s got %s", $expected, \get_debug_type($value));
    }

    public static function create(string $expected, mixed $value): self
    {
        return new self(self::message($expected, $value));
    }
}
