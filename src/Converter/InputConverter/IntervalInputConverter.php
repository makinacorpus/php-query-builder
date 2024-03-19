<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter\InputConverter;

use MakinaCorpus\QueryBuilder\Converter\ConverterContext;
use MakinaCorpus\QueryBuilder\Converter\InputConverter;
use MakinaCorpus\QueryBuilder\Converter\InputTypeGuesser;
use MakinaCorpus\QueryBuilder\Error\UnexpectedInputValueTypeError;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Be aware that this will probably only work with PostgreSQL.
 *
 * Interval should be injected as expressions in the query instead whenever
 * possible. Especially when not using PostgreSQL.
 */
class IntervalInputConverter implements InputConverter, InputTypeGuesser
{
    /**
     * Format interval as an ISO8601 string.
     */
    public static function intervalToIso8601(\DateInterval $interval): string
    {
        // All credits to https://stackoverflow.com/a/33787489
        $string = $interval->format("P%yY%mM%dDT%hH%iM%sS");

        // I would prefer a single \str_replace() \strtr() call but it seems that
        // PHP does not guarante order in replacements, and we experienced
        // different behaviors depending upon PHP versions.
        $replacements = [
            "M0S" => "M",
            "H0M" => "H",
            "T0H" => "T",
            "D0H" => "D",
            "M0D" => "M",
            "Y0M" => "Y",
            "P0Y" => "P",
        ];
        foreach ($replacements as $search => $replace) {
            $string = \str_replace($search, $replace, $string);
        }

        return $string;
    }

    #[\Override]
    public function supportedInputTypes(): array
    {
        return [
            'interval',
        ];
    }

    #[\Override]
    public function guessInputType(mixed $value): null|string|Type
    {
        if ($value instanceof \DateInterval) {
            return Type::dateInterval();
        }
        return null;
    }

    #[\Override]
    public function toSql(Type $type, mixed $value, ConverterContext $context): null|int|float|string
    {
        if (!$value instanceof \DateInterval) {
            throw UnexpectedInputValueTypeError::create(\DateInterval::class, $value);
        }
        return self::intervalToIso8601($value);
    }
}
