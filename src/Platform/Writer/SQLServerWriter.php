<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Writer;

use MakinaCorpus\QueryBuilder\Expression\Aggregate;
use MakinaCorpus\QueryBuilder\Expression\Cast;
use MakinaCorpus\QueryBuilder\Expression\Concat;
use MakinaCorpus\QueryBuilder\Expression\CurrentTimestamp;
use MakinaCorpus\QueryBuilder\Expression\Modulo;
use MakinaCorpus\QueryBuilder\Expression\Random;
use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\Writer\Writer;
use MakinaCorpus\QueryBuilder\Writer\WriterContext;

/**
 * SQLServer >= 2019
 */
class SQLServerWriter extends Writer
{
    // @see doModifyLimitQuery() in doctrine/dbal

    /**
     * {@inheritdoc}
     *
     * SQLServer aggregate function names seems to be keywords, not functions.
     */
    protected function shouldEscapeAggregateFunctionName(): bool
    {
        return false;
    }
 
    /**
     * {@inheritdoc}
     */
    protected function formatCurrentTimetamp(CurrentTimestamp $expression, WriterContext $context): string
    {
        return 'getdate()';
    }

    /**
     * {@inheritdoc}
     */
    protected function formatRandom(Random $expression, WriterContext $context): string
    {
        return 'rand()';
    }

    /**
     * {@inheritdoc}
     *
     * https://modern-sql.com/feature/filter
     */
    protected function formatAggregate(Aggregate $expression, WriterContext $context): string
    {
        return $this->doFormatAggregateWithoutFilter($expression, $context);
    }

    /**
     * Format a function call.
     */
    protected function formatConcat(Concat $expression, WriterContext $context): string
    {
        $output = '';
        foreach ($expression->getArguments() as $argument) {
            if ($output) {
                $output .= ', ';
            }
            $output .= $this->format($argument, $context);
        }

        return 'CONCAT(' . $output . ')';
    }

    /**
     * {@inheritdoc}
     */
    protected function doFormatOutputNewRowIdentifier(TableName $table): string
    {
        return 'inserted';
    }

    /**
     * {@inheritdoc}
     */
    protected function doFormatOutputOldRowIdentifier(TableName $table): string
    {
        return 'deleted';
    }

    /**
     * {@inheritdoc}
     *
     * SQL Server uses the OUTPUT clause, which is far more advanced than
     * simply returning whatever was mutated, it can deambiguate between
     * DELETED (row prior mutation) and INSERTED (row after mutation).
     *
     * We don't support that use case.
     *
     * Nevertheless it enforces the user to specify whichever value you really
     * require, the old or the new one. By default, and without anything
     * specified, it will always be the new one.
     */
    protected function doFormatReturning(WriterContext $context, array $return, ?string $escapedTableName = null): string
    {
        return 'output ' . $this->doFormatSelect($context, $return, $escapedTableName);
    }

    /**
     * {@inheritdoc}
     *
     * WARNING DANGER, SQLServer requires an ORDER BY in the query for having
     * a limit/offset. It's what it is.
     */
    protected function doFormatRange(WriterContext $context, int $limit = 0, int $offset = 0, bool $hasOrder = true): string
    {
        $ret = '';
        if ($offset) {
            $ret .= 'offset ' . $offset . ' rows';
        }
        if ($limit) {
            if (!$offset) {
                $ret .= 'offset 0 rows';
            }
            $ret .= ' fetch next ' . $limit . ' rows only';
        }
        if ($ret && !$hasOrder) {
            return 'order by 1 ' . $ret;
        }
        return $ret;
    }
}
