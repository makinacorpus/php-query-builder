<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Writer;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\CurrentDatabase;
use MakinaCorpus\QueryBuilder\Expression\CurrentSchema;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Platform\Type\PostgreSQLTypeConverter;
use MakinaCorpus\QueryBuilder\Query\Merge;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Type\TypeConverter;
use MakinaCorpus\QueryBuilder\Writer\Writer;
use MakinaCorpus\QueryBuilder\Writer\WriterContext;

/**
 * PostgreSQL >= 8.4.
 *
 * Activily tested with versions from 9.5 to 16.
 */
class PostgreSQLWriter extends Writer
{
    #[\Override]
    protected function createTypeConverter(): TypeConverter
    {
        return new PostgreSQLTypeConverter();
    }

    #[\Override]
    protected function formatCurrentDatabase(CurrentDatabase $expression, WriterContext $context): string
    {
        return 'CURRENT_DATABASE()';
    }

    #[\Override]
    protected function formatCurrentSchema(CurrentSchema $expression, WriterContext $context): string
    {
        return 'CURRENT_SCHEMA()';
    }

    #[\Override]
    protected function doFormatInsertNoValuesStatement(WriterContext $context): string
    {
        return "DEFAULT VALUES";
    }

    /**
     * This is a copy-paste of formatQueryInsertValues(). In 2.x formatter will
     * be refactored to avoid such copy/paste.
     */
    #[\Override]
    protected function formatMerge(Merge $query, WriterContext $context): string
    {
        $output = [];

        $columns = $query->getAllColumns();
        $table = $query->getTable();

        $output[] = $this->doFormatWith($context, $query->getAllWith());
        // From SQL 92 standard, INSERT queries don't have table alias
        $output[] = 'insert into ' . $this->escaper->escapeIdentifier($table->getName());

        // @todo skip column names if numerical
        if ($columns) {
            $output[] = '(' . $this->doFormatColumnNameList($context, $columns) . ')';
        }

        $output[] = $this->format($query->getQuery(), $context);

        switch ($mode = $query->getConflictBehaviour()) {

            case Query::CONFLICT_IGNORE:
                // Do nothing.
                $output[] = "on conflict do nothing";
                break;

            case Query::CONFLICT_UPDATE:
                $key = $query->getKey();
                if (!$key) {
                    throw new QueryBuilderError(\sprintf("Key must be specified calling %s::setKey() when on conflict update is set.", \get_class($query)));
                }

                // Exclude primary key from the UPDATE statement.
                $setColumnMap = [];
                foreach ($columns as $column) {
                    if (!\in_array($column, $key)) {
                        $setColumnMap[$column] = new Raw("excluded." . $this->escaper->escapeIdentifier($column));
                    }
                }
                $output[] = 'on conflict (' . $this->doFormatColumnNameList($context, $key) . ')';
                $output[] = 'do update set';
                $output[] = $this->doFormatUpdateSet($context, $setColumnMap);
                break;

            default:
                throw new QueryBuilderError(\sprintf("Unsupported merge conflict mode: %s", (string) $mode));
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = $this->doFormatReturning($context, $return, $table->getAlias());
        }

        return \implode("\n", $output);
    }
}
