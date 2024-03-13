<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;

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
    const UNIT_MILLISECOND = 'millisecond';
    const UNIT_MICROSECOND = 'microsecond';
    const UNIT_SECOND = 'second';
    const UNIT_MINUTE = 'minute';
    const UNIT_HOUR = 'hour';
    const UNIT_DAY = 'day';
    const UNIT_WEEK = 'week';
    const UNIT_MONTH = 'month';
    const UNIT_QUARTER = 'quarter';
    const UNIT_YEAR = 'year';
    const UNIT_DECADE = 'decade';
    const UNIT_CENTURY = 'century';
    const UNIT_MILLENIUM = 'millenium';

    /** @var array<string,int> */
    private array $values;
    private bool $tainted = false;

    /**
     * @param \DateInterval|array<string,int> $values
     *   If an array given, keys are units and values are integers.
     */
    public function __construct(
        \DateInterval|array $values,
    ) {
        if ($values instanceof \DateInterval) {
            $this->values = [];
            if ($values->y) {
                $this->values[self::UNIT_YEAR] = $values->y;
            }
            if ($values->m) {
                $this->values[self::UNIT_MONTH] = $values->m;
            }
            if ($values->d) {
                $this->values[self::UNIT_DAY] = $values->d;
            }
            if ($values->h) {
                $this->values[self::UNIT_HOUR] = $values->h;
            }
            if ($values->i) {
                $this->values[self::UNIT_MINUTE] = $values->i;
            }
            if ($values->s) {
                $this->values[self::UNIT_SECOND] = $values->s;
            }
            if ($values->f) {
                $this->values[self::UNIT_MICROSECOND] = \floor($values->f * 1000);
            }
        } else {
            $this->values = [];
            foreach ($values as $unit => $value) {
                if (!\is_int($value)) {
                    throw new QueryBuilderError(\sprintf("When value is an array of unit => value, values must integers, '%s' found for unit '%s'.", \get_debug_type($value), $unit));
                }
                if (!\is_string($unit)) {
                    throw new QueryBuilderError(\sprintf("When value is an array of unit => value, units must strings, '%s' found.", \get_debug_type($unit)));
                }

                // First normalize user given value.
                $unit = \strtolower($unit);
                if (\str_ends_with($unit, 's')) {
                    $unit = \substr($unit, 0, -1);
                }

                // Allow some synonyms.
                $unit = match ($unit) {
                    'millisec' => self::UNIT_MILLISECOND,
                    'min' => self::UNIT_MINUTE,
                    'msec' => self::UNIT_MICROSECOND,
                    'sec' => self::UNIT_CENTURY,
                    default => $unit,
                };

                // Proceed to some safe conversions.
                switch ($unit) {
                    case self::UNIT_MILLENIUM:
                        $unit = self::UNIT_YEAR;
                        $value *= 1000;
                        break;
                    case self::UNIT_CENTURY:
                        $unit = self::UNIT_YEAR;
                        $value *= 100;
                        break;
                    case self::UNIT_DECADE:
                        $unit = self::UNIT_YEAR;
                        $value *= 10;
                        break;
                    case self::UNIT_YEAR:
                        break;
                    case self::UNIT_QUARTER:
                        $unit = self::UNIT_MONTH;
                        $value *= 3;
                        break;
                    case self::UNIT_MONTH:
                        break;
                    case self::UNIT_WEEK:
                        $unit = self::UNIT_DAY;
                        $value *= 7;
                        break;
                    case self::UNIT_DAY:
                        break;
                    case self::UNIT_HOUR:
                        break;
                    case self::UNIT_MINUTE:
                        break;
                    case self::UNIT_SECOND:
                        break;
                    case self::UNIT_MICROSECOND:
                        break;
                    case self::UNIT_MILLISECOND:
                        break;
                    default:
                        $this->tainted = true;
                        break;
                }
                if (\array_key_exists($unit, $this->values)) {
                    $this->values[$unit] += $value;
                } else {
                    $this->values[$unit] = $value;
                }
            }
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
    public function returnType(): ?string
    {
        return 'interval';
    }

    /** @return array<string,int> */
    public function getValues(): array
    {
        return $this->values;
    }
}
