<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Writer;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Row;
use MakinaCorpus\QueryBuilder\Writer\WriterContext;

/**
 * MySQL >= 8.
 */
class MySQL8Writer extends MySQLWriter
{
    /**
     * {@inheritdoc}
     */
    protected function doFormatInsertExcludedItem($expression): Expression
    {
        if (\is_string($expression)) {
            // Let pass strings with dot inside, it might already been formatted.
            if (false !== \strpos($expression, ".")) {
                return new Raw($expression);
            }
            return new Raw("new." . $this->escaper->escapeIdentifier($expression));
        }

        return $expression;
    }

    /**
     * {@inheritdoc}
     *
     * MySQL VALUES syntax is:
     *    VALUES ROW(?, ?), ROW(?, ?), ...
     *
     * Which differs from SQL standard one which is:
     *    VALUES (?, ?), (?, ?), ...
     *
     * For what it worth, I was really surprised to see MySQL 8 supporting
     * table name and column alias for VALUES statement!
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/values.html
     */
    protected function doFormatConstantTableRow(Row $expression, WriterContext $context, bool $inInsert = false): string
    {
        if ($inInsert) {
            return $this->formatRow($expression, $context);
        }
        return 'row ' . $this->formatRow($expression, $context);
    }
}
