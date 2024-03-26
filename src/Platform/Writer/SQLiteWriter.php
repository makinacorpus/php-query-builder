<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Writer;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Error\UnsupportedFeatureError;
use MakinaCorpus\QueryBuilder\Expression\CurrentDatabase;
use MakinaCorpus\QueryBuilder\Expression\CurrentSchema;
use MakinaCorpus\QueryBuilder\Expression\DateAdd;
use MakinaCorpus\QueryBuilder\Expression\DateInterval;
use MakinaCorpus\QueryBuilder\Expression\DateSub;
use MakinaCorpus\QueryBuilder\Expression\Lpad;
use MakinaCorpus\QueryBuilder\Expression\Random;
use MakinaCorpus\QueryBuilder\Expression\RandomInt;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Platform\Type\SQLiteTypeConverter;
use MakinaCorpus\QueryBuilder\Query\Delete;
use MakinaCorpus\QueryBuilder\Type\TypeConverter;
use MakinaCorpus\QueryBuilder\Writer\Writer;
use MakinaCorpus\QueryBuilder\Writer\WriterContext;

/**
 * SQLite.
 *
 * Tested with SQLite >= 3.
 */
class SQLiteWriter extends Writer
{
    #[\Override]
    protected function createTypeConverter(): TypeConverter
    {
        return new SQLiteTypeConverter();
    }

    #[\Override]
    protected function formatCurrentDatabase(CurrentDatabase $expression, WriterContext $context): string
    {
        return "'main'";
    }

    #[\Override]
    protected function formatCurrentSchema(CurrentSchema $expression, WriterContext $context): string
    {
        return "'public'";
    }

    /**
     * Format a function call.
     *
     * This is non standard SQL, and returns the PostgreSQL variant.
     */
    protected function formatRandom(Random $expression, WriterContext $context): string
    {
        return 'random()';
    }

    /**
     * Format a function call.
     */
    protected function formatRandomInt(RandomInt $expression, WriterContext $context): string
    {
        $min = $expression->getMin();
        $max = $expression->getMax();

        if ($max < $min) {
            $max = $min;
            $min = $expression->getMax();
        }

        return $this->formatRaw(
            new Raw(
                'abs(random()) % (? - ?) + ?',
                [$max, $min, $min]
            ),
            $context,
        );
    }

    #[\Override]
    protected function formatDateAdd(DateAdd $expression, WriterContext $context): string
    {
        $interval = $expression->getInterval();

        if ($interval instanceof DateInterval) {
            $ret = $this->format($expression->getDate(), $context);

            foreach ($interval->getValues() as $unit) {
                $intervalStr = $this->formatDateIntervalUnit($unit, $context);

                $ret = 'datetime(' . $ret . ', ' . $intervalStr . ')';
            }
        } else {
            $ret = 'datetime(' . $this->format($expression->getDate(), $context) . ', ' . $this->format($expression->getInterval(), $context) . ')';
        }

        return $ret;
    }

    #[\Override]
    protected function formatDateSub(DateSub $expression, WriterContext $context): string
    {
        $interval = $expression->getInterval();

        if ($interval instanceof DateInterval) {
            $ret = $this->format($expression->getDate(), $context);

            foreach ($interval->getValues() as $unit) {
                $intervalStr = $this->formatDateIntervalUnit($unit, $context, true);

                $ret = 'datetime(' . $ret . ', ' . $intervalStr . ')';
            }
        } else {
            throw new UnsupportedFeatureError("SQLite cannot format date substraction if interval is an arbitrary expression which is not an interval.");
        }

        return $ret;
    }

    #[\Override]
    protected function formatDateInterval(DateInterval $expression, WriterContext $context): string
    {
        throw new UnsupportedFeatureError('SQLite does not know the interval type.');
    }

    #[\Override]
    protected function formatLpad(Lpad $expression, WriterContext $context): string
    {
        list($value, $size, $fill) = $this->doGetPadArguments($expression, $context);

        // @see https://stackoverflow.com/questions/6576343/how-to-emulate-lpad-rpad-with-sqlite
        return $this->formatRaw(
            new Raw(
                <<<SQL
                substr(
                    replace(
                        hex(zeroblob(?)),
                        '00',
                        ?
                    ), 1, ? - length(?)
                ) || ?
                SQL,
                [$size, $fill, $size, $value, $value],
            ),
            $context
        );
    }

    #[\Override]
    protected function formatRpad(Lpad $expression, WriterContext $context): string
    {
        list($value, $size, $fill) = $this->doGetPadArguments($expression, $context);

        // @see https://stackoverflow.com/questions/6576343/how-to-emulate-lpad-rpad-with-sqlite
        return $this->formatRaw(
            new Raw(
                <<<SQL
                ? || substr(
                    replace(
                        hex(zeroblob(?)),
                        '00',
                        ?
                    ), 1, ? - length(?)
                )
                SQL,
                [$value, $size, $fill, $size, $value],
            ),
            $context
        );
    }

    #[\Override]
    protected function doFormatInsertNoValuesStatement(WriterContext $context): string
    {
        return "DEFAULT VALUES";
    }

    /**
     * SQLite does not support OFFSET alone.
     */
    #[\Override]
    protected function doFormatRange(WriterContext $context, int $limit = 0, int $offset = 0, bool $hasOrder = true): string
    {
        if ($limit) {
            if (!$offset) {
                return 'limit ' . $limit;
            }
            return 'limit ' . $limit . ' offset ' . $offset;
        }
        if ($offset) {
            return 'limit -1 offset ' . $offset;
        }
        return '';
    }

    #[\Override]
    protected function formatDelete(Delete $query, WriterContext $context): string
    {
        $from = $query->getAllFrom();

        if (1 < \count($from)) {
            throw new QueryBuilderError("SQLite doesn't support DELETE USING|JOIN");
        }

        $join = $query->getAllJoin();
        if ($join) {
            throw new QueryBuilderError("SQLite doesn't support DELETE USING|JOIN");
        }

        return parent::formatDelete($query, $context);
    }
}
