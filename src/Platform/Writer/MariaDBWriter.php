<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Writer;

use MakinaCorpus\QueryBuilder\Expression\Row;
use MakinaCorpus\QueryBuilder\Platform\Type\MariaDBTypeConverter;
use MakinaCorpus\QueryBuilder\Type\TypeConverter;
use MakinaCorpus\QueryBuilder\Writer\WriterContext;

/**
 * MariaDB >= 10.
 */
class MariaDBWriter extends MySQL8Writer
{
    #[\Override]
    protected function createTypeConverter(): TypeConverter
    {
        return new MariaDBTypeConverter();
    }

    /**
     * MariaDB >= 10.3 supports standard SQL VALUES statement.
     * Nevertheless, it doesn't support aliasing column names of values.
     *
     * @see https://modern-sql.com/blog/2018-08/whats-new-in-mariadb-10.3
     * @see https://modern-sql.com/use-case/naming-unnamed-columns#with
     * @see https://dba.stackexchange.com/questions/177312/does-mariadb-or-mysql-implement-the-values-expression-table-value-constructor
     */
    #[\Override]
    protected function doFormatConstantTableRow(Row $expression, WriterContext $context, bool $inInsert = false): string
    {
        return $this->formatRow($expression, $context);
    }
}
