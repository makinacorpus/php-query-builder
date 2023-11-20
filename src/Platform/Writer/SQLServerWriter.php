<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Writer;

use MakinaCorpus\QueryBuilder\Expression\Aggregate;
use MakinaCorpus\QueryBuilder\Expression\CurrentTimestamp;
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
     *
     * https://modern-sql.com/feature/filter
     */
    protected function formatAggregate(Aggregate $expression, WriterContext $context): string
    {
        return $this->doFormatAggregateWithoutFilter($expression, $context);
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
