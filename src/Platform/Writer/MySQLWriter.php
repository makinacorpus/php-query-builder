<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Writer;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\Concat;
use MakinaCorpus\QueryBuilder\Expression\Random;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Query\Delete;
use MakinaCorpus\QueryBuilder\Query\Merge;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Query\Update;
use MakinaCorpus\QueryBuilder\Writer\Writer;
use MakinaCorpus\QueryBuilder\Writer\WriterContext;

/**
 * MySQL <= 5.7.
 */
class MySQLWriter extends Writer
{
    /**
     * {@inheritdoc}
     */
    protected function doFormatInsertNoValuesStatement(WriterContext $context) : string
    {
        return "() VALUES ()";
    }

    /**
     * Format excluded item from INSERT or MERGE values.
     */
    protected function doFormatInsertExcludedItem($expression): Expression
    {
        if (\is_string($expression)) {
            // Let pass strings with dot inside, it might already been formatted.
            if (false !== \strpos($expression, ".")) {
                return new Raw($expression);
            }
            return new Raw("values(" . $this->escaper->escapeIdentifier($expression) . ")");
        }

        return $expression;
    }

    /**
     * This is a copy-paste of formatQueryInsertValues(). In 2.x formatter will
     * be refactored to avoid such copy/paste.
     *
     * {@inheritdoc}
     */
    protected function formatMerge(Merge $query, WriterContext $context) : string
    {
        $output = [];

        $columns = $query->getAllColumns();
        $isIgnore = Query::CONFLICT_IGNORE === $query->getConflictBehaviour();

        if (!$table = $query->getTable()) {
            throw new QueryBuilderError("Insert query must have a table.");
        }

        $output[] = $this->doFormatWith($context, $query->getAllWith());
        if ($isIgnore) {
            // From SQL 92 standard, INSERT queries don't have table alias
            $output[] = 'insert ignore into ' . $this->escaper->escapeIdentifier($table->getName());
        } else {
            // From SQL 92 standard, INSERT queries don't have table alias
            $output[] = 'insert into ' . $this->escaper->escapeIdentifier($table->getName());
        }

        if ($columns) {
            $output[] = '(' . $this->doFormatColumnNameList($context, $columns) . ')';
        }

        $output[] = $this->format($query->getQuery(), $context);

        if (!$isIgnore) {
            switch ($mode = $query->getConflictBehaviour()) {

                case Query::CONFLICT_UPDATE:
                    // Exclude primary key from the UPDATE statement.
                    $key = $query->getKey();
                    $setColumnMap = [];
                    foreach ($columns as $column) {
                        if (!\in_array($column, $key)) {
                            $setColumnMap[$column] = $this->doFormatInsertExcludedItem($column);
                        }
                    }
                    $output[] = "on duplicate key update";
                    $output[] = $this->doFormatUpdateSet($context, $setColumnMap);
                    break;

                default:
                    throw new QueryBuilderError(\sprintf("Unsupported merge conflict mode: %s", (string) $mode));
            }
        }

        $return = $query->getAllReturn();
        if ($return) {
            throw new QueryBuilderError("MySQL does not support RETURNING SQL clause");
        }

        return \implode("\n", $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function formatDelete(Delete $query, WriterContext $context) : string
    {
        $output = [];

        // MySQL need to specify on which table to delete from if there is an
        // alias on the main table, so we are going to give him this always
        // so we won't have to bother about weither or not we have other tables
        // to JOIN.
        if (!$table = $query->getTable()) {
            throw new QueryBuilderError("Delete query must have a table.");
        }

        $tableAlias = $table->getAlias() ?? $table->getName();

        // MySQL does not have USING clause, and support a non-standard way of
        // writing DELETE directly using FROM .. JOIN clauses, just like you
        // would write a SELECT, so give him that. Beware that some MySQL
        // versions will DELETE FROM all tables matching rows in the FROM,
        // hence the "table_alias.*" statement here.
        $output[] = 'delete ' . $this->escaper->escapeIdentifier($tableAlias) .  '.* from ' . $this->format($table, $context, true);

        $from = $query->getAllFrom();
        if ($from) {
            $output[] = ', ';
            $output[] = $this->doFormatFrom($context, $from, null);
        }

        $join = $query->getAllJoin();
        if ($join) {
            $output[] = $this->doFormatJoin($context, $join);
        }

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = 'where ' . $this->format($where, $context, true);
        }

        $return = $query->getAllReturn();
        if ($return) {
            throw new QueryBuilderError("MySQL does not support RETURNING SQL clause");
        }

        return \implode("\n", \array_filter($output));
    }

    /**
     * {@inheritdoc}
     */
    protected function formatUpdate(Update $query, WriterContext $context) : string
    {
        $output = [];

        $columns = $query->getUpdatedColumns();
        if (empty($columns)) {
            throw new QueryBuilderError("cannot run an update query without any columns to update");
        }

        // From the SQL 92 standard (which PostgreSQL does support here) the
        // FROM and JOIN must be written AFTER the SET clause. MySQL does not.
        $output[] = 'update ' . $this->format($query->getTable(), $context);

        // MySQL don't do UPDATE t1 SET [...] FROM t2 but uses the SELECT
        // syntax and just append the set after the JOIN clause.
        $from = $query->getAllFrom();
        if ($from) {
            $output[] = ', ';
            $output[] = $this->doFormatFrom($context, $from, null);
        }

        $join = $query->getAllJoin();
        if ($join) {
            $output[] = $this->doFormatJoin($context, $join);
        }

        // SET clause.
        $output[] = 'set ' . $this->doFormatUpdateSet($context, $columns) . "\n";

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = 'where ' . $this->formatWhere($context, $where);
        }

        $return = $query->getAllReturn();
        if ($return) {
            throw new QueryBuilderError("MySQL does not support RETURNING SQL clause");
        }

        return \implode("\n", $output);
    }

    /**
     * {@inheritdoc}
     *
     * MySQL and types, seriously. Be conservative and fix user basic
     * errors, but do not attempt to do too much magic and let unknown
     * types pass.
     *
     * @see https://dev.mysql.com/doc/refman/8.2/en/cast-functions.html#function_cast
     */
    protected function doFormatCastExpression(string $expressionString, string $type, WriterContext $context): string
    {
        $isArray = false;

        if (\str_ends_with($type, '[]')) {
            $type = \substr($type, 0, -2);
            $isArray = true;
        }

        // Do not use "unsigned" on behalf of the user, or it would proceed
        // accidentally to transparent data alteration.
        if (\in_array(\strtolower($type), ['int', 'integer', 'int4', 'int8', 'tinyint', 'smallint', 'bigint', 'serial', 'bigserial'])) {
            $type = 'SIGNED';
        } else if (\in_array(\strtolower($type), ['text', 'string', 'varchar'])) {
            $type = 'CHAR';
        }

        if ($isArray) {
            $type .= ' ARRAY';
        }

        return 'CAST(' . $expressionString . ' AS ' . $type . ')';
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
     * Format a function call.
     *
     * This is non standard SQL, and returns the PostgreSQL variant.
     */
    protected function formatRandom(Random $expression, WriterContext $context): string
    {
        return 'rand()';
    }

    /**
     * {@inheritdoc}
     *
    protected function formatSimilarTo(SimilarTo $value, WriterContext $context): string
    {
        if ($value->hasValue()) {
            $pattern = $value->getPattern(
                $this->escaper->escapeLike(
                    $value->getUnsafeValue()
                )
            );
        } else {
            $pattern = $value->getPattern();
        }

        // MySQL doesn't like the ILIKE operator, at least not prior to 8.x.
        switch ($operator = $value->getOperator()) {
            case Where::ILIKE:
                $operator = Where::LIKE;
                break;
            case Where::NOT_ILIKE:
                $operator = Where::NOT_LIKE;
                break;
            default:
                break;
        }

        return $this->format($value->getColumn(), $context) . ' ' . $operator . ' ' . $this->escaper->escapeLiteral($pattern);
    }
     */
}
