<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Writer;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Row;
use MakinaCorpus\QueryBuilder\Platform\Type\MySQL8TypeConverter;
use MakinaCorpus\QueryBuilder\Type\InternalType;
use MakinaCorpus\QueryBuilder\Type\Type;
use MakinaCorpus\QueryBuilder\Type\TypeConverter;
use MakinaCorpus\QueryBuilder\Writer\WriterContext;

/**
 * MySQL >= 8.
 *
 * @see https://stackoverflow.com/questions/255517/mysql-offset-infinite-rows
 */
class MySQL8Writer extends MySQLWriter
{
    #[\Override]
    protected function createTypeConverter(): TypeConverter
    {
        return new MySQL8TypeConverter();
    }

    #[\Override]
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
     * Same code as MySQL 5.7 but drops the DECIMAL since MySQL >= 8.0 supports
     * the FLOAT type cast.
     */
    #[\Override]
    protected function doFormatCastType(Type $type, WriterContext $context): ?string
    {
        if ($type->isText()) {
            return 'CHAR';
        }
        // Do not use "unsigned" on behalf of the user, or it would proceed
        // accidentally to transparent data alteration.
        if (\in_array($type->internal, [InternalType::INT, InternalType::INT_BIG, InternalType::INT_SMALL, InternalType::INT_TINY])) {
            return 'SIGNED';
        }
        return null;
    }

    /**
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
    #[\Override]
    protected function doFormatConstantTableRow(Row $expression, WriterContext $context, bool $inInsert = false): string
    {
        if ($inInsert) {
            return $this->formatRow($expression, $context);
        }
        return 'row ' . $this->formatRow($expression, $context);
    }
}
