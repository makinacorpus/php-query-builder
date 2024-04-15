<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Type\Type;

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
class DateInterval implements Expression
{
    public const UNIT_MILLISECOND = 'millisecond';
    public const UNIT_MICROSECOND = 'microsecond';
    public const UNIT_SECOND = 'second';
    public const UNIT_MINUTE = 'minute';
    public const UNIT_HOUR = 'hour';
    public const UNIT_DAY = 'day';
    public const UNIT_WEEK = 'week';
    public const UNIT_MONTH = 'month';
    public const UNIT_QUARTER = 'quarter';
    public const UNIT_YEAR = 'year';
    public const UNIT_DECADE = 'decade';
    public const UNIT_CENTURY = 'century';
    public const UNIT_MILLENIUM = 'millenium';

    /** @var array<string,int> */
    private array $raw = [];
    /** @var array<DateIntervalUnit> */
    private array $values = [];

    /**
     * @param \DateInterval|array<string,int> $values
     *   If an array given, keys are units and values are integers.
     */
    public function __construct(
        DateIntervalUnit|\DateInterval|array|string $values,
    ) {
        if ($values instanceof DateIntervalUnit) {
            $this->values[] = $values;

            return;
        }

        if (\is_string($values)) {
            try {
                $values = new \DateInterval($values);
            } catch (\Throwable $e) {
                if (!$values = \DateInterval::createFromDateString($values)) {
                    throw new QueryBuilderError(\sprintf("Given interval string '%s' is invalid", $values));
                }
            }
        }

        if ($values instanceof \DateInterval) {
            if ($values->y) {
                $this->raw[DateIntervalUnit::YEAR] = $values->y;
            }
            if ($values->m) {
                $this->raw[DateIntervalUnit::MONTH] = $values->m;
            }
            if ($values->d) {
                $this->raw[DateIntervalUnit::DAY] = $values->d;
            }
            if ($values->h) {
                $this->raw[DateIntervalUnit::HOUR] = $values->h;
            }
            if ($values->i) {
                $this->raw[DateIntervalUnit::MINUTE] = $values->i;
            }
            if ($values->s) {
                $this->raw[DateIntervalUnit::SECOND] = $values->s;
            }
            if ($values->f) {
                $this->raw[DateIntervalUnit::MICROSECOND] = \floor($values->f * 1000);
            }
        } else {
            foreach ($values as $unit => $value) {
                if (!\is_int($value)) {
                    throw new QueryBuilderError(\sprintf("When value is an array of unit => value, values must integers, '%s' found for unit '%s'.", \get_debug_type($value), $unit));
                }
                if (!\is_string($unit)) {
                    throw new QueryBuilderError(\sprintf("When value is an array of unit => value, units must strings, '%s' found.", \get_debug_type($unit)));
                }

                [$value, $unit] = DateIntervalUnit::safeReduce($value, $unit);

                if (\array_key_exists($unit, $this->raw)) {
                    $this->raw[$unit] += $value;
                } else {
                    $this->raw[$unit] = $value;
                }
            }
        }

        foreach ($this->raw as $unit => $value) {
            $this->values[] = new DateIntervalUnit($value, $unit);
        }
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
    public function returnType(): ?Type
    {
        return Type::dateInterval();
    }

    /** @return array<DateIntervalUnit> */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @internal
     *   For unit tests mostly.
     */
    public function toArray(): array
    {
        return $this->raw;
    }
}
