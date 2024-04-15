<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Converter\OutputConverter;

use MakinaCorpus\QueryBuilder\Converter\ConverterContext;
use MakinaCorpus\QueryBuilder\Converter\OutputConverter;

class IntervalOutputConverter implements OutputConverter
{
    #[\Override]
    public function supportedOutputTypes(): array
    {
        return [
            \DateInterval::class,
        ];
    }

    /**
     * Convert PostgreSQL formatted string to \DateInterval.
     */
    public static function extractPostgreSQLAsInterval(string $value): \DateInterval
    {
        if ('P' === $value[0] && !\strpos(' ', $value)) {
            return new \DateInterval($value);
        }

        $pos = null;
        if (false === ($pos = strrpos($value, ' '))) {
            // Got ourselves a nice "01:23:56" string
            list($hour, $min, $sec) = \explode(':', $value);
            return \DateInterval::createFromDateString(\sprintf("%d hour %d min %d sec", $hour, $min, $sec));
        } else if (false === \strpos($value, ':')) {
            // Got ourselves a nice "1 year ..." string
            return \DateInterval::createFromDateString(\strtr($value, [
                'mons' => 'months',
                'mon' => 'month',
            ]));
        } else {
            // Mixed PostgreSQL format "1 year... HH:MM:SS"
            $date = \mb_substr($value, 0, $pos);
            $time = \mb_substr($value, $pos + 1);
            list($hour, $min, $sec) = \explode(':', $time);
            return \DateInterval::createFromDateString(\sprintf("%s %d hour %d min %d sec", $date, $hour, $min, $sec));
        }
    }

    #[\Override]
    public function fromSql(string $type, int|float|string $value, ConverterContext $context): mixed
    {
        return self::extractPostgreSQLAsInterval((string) $value);
    }
}
