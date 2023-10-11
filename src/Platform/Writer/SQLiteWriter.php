<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Writer;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Query\Delete;
use MakinaCorpus\QueryBuilder\Writer\Writer;
use MakinaCorpus\QueryBuilder\Writer\WriterContext;

/**
 * SQLite.
 *
 * Tested with SQLite >= 3.
 */
class SQLiteWriter extends Writer
{
    /**
     * {@inheritdoc}
     */
    protected function doFormatInsertNoValuesStatement(WriterContext $context): string
    {
        return "DEFAULT VALUES";
    }

    /**
     * {@inheritdoc}
     */
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
