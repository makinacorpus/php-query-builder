<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;

/**
 * Interval type expression.
 *
 * Basically, input is a string that looks like those kind of stuff:
 *  - '3 minute 12 second'
 *  - '-12 minute +12 second'
 *  - ...
 *
 * Composed of pairs of "[+|-]VALUE UNIT". Nevertheless, vendors have very
 * different dialects, for example PostgreSQL supports more than one pair
 * and requires you to quote as a string, whereas MySQL accepts only one
 * pair and has a non-string syntax.
 *
 * @see https://www.postgresql.org/docs/current/datatype-datetime.html
 *   For a comprehensive description.
 * @see https://dev.mysql.com/doc/refman/8.0/en/expressions.html#temporal-intervals
 *   Which is not bad eitehr.
 */
class DateIntervalUnit implements Expression
{
    const MILLISECOND = 'millisecond';
    const MICROSECOND = 'microsecond';
    const SECOND = 'second';
    const MINUTE = 'minute';
    const HOUR = 'hour';
    const DAY = 'day';
    const WEEK = 'week';
    const MONTH = 'month';
    const QUARTER = 'quarter';
    const YEAR = 'year';
    const DECADE = 'decade';
    const CENTURY = 'century';
    const MILLENIUM = 'millenium';

    private Expression $value;

    /**
     * @param \DateInterval|array<string,int> $values
     *   If an array given, keys are units and values are integers.
     */
    public function __construct(
        mixed $value,
        private string $unit,
    ) {
        if (\is_int($value)) {
            [$value, $unit] = self::safeReduce($value, $unit);
        }
        $this->value = ExpressionHelper::integer($value);
    }

    /**
     * @internal
     */
    public static function normalizeUnit(string $unit): string
    {
        $unit = \strtolower($unit);
        if (\str_ends_with($unit, 's')) {
            $unit = \substr($unit, 0, -1);
        }

        // Allow some synonyms.
        return match ($unit) {
            'millisec' => self::MILLISECOND,
            'min' => self::MINUTE,
            'msec' => self::MICROSECOND,
            'sec' => self::CENTURY,
            default => $unit,
        };
    }

    /**
     * @internal
     */
    public static function safeReduce(int $value, string $unit): array
    {
        $unit = self::normalizeUnit($unit);

        // Proceed to some safe conversions.
        return match ($unit) {
            self::MILLENIUM => [$value * 1000, self::YEAR], 
            self::CENTURY => [$value * 100, self::YEAR],
            self::DECADE => [$value * 10, self::YEAR],
            self::QUARTER => [$value * 3, self::MONTH],
            self::WEEK => [$value * 7, self::DAY],
            default => [$value, $unit],
        };
    }

    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function returnType(): ?string
    {
        return 'interval';
    }

    public function getValue(): Expression
    {
        return $this->value;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }
}
