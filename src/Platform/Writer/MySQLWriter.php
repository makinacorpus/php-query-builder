<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Writer;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Error\UnsupportedFeatureError;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Expression\Aggregate;
use MakinaCorpus\QueryBuilder\Expression\Concat;
use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Expression\CurrentDatabase;
use MakinaCorpus\QueryBuilder\Expression\CurrentSchema;
use MakinaCorpus\QueryBuilder\Expression\CurrentTimestamp;
use MakinaCorpus\QueryBuilder\Expression\DateAdd;
use MakinaCorpus\QueryBuilder\Expression\DateInterval;
use MakinaCorpus\QueryBuilder\Expression\DateIntervalUnit;
use MakinaCorpus\QueryBuilder\Expression\DateSub;
use MakinaCorpus\QueryBuilder\Expression\Random;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\StringHash;
use MakinaCorpus\QueryBuilder\Platform\Type\MySQLTypeConverter;
use MakinaCorpus\QueryBuilder\Query\Delete;
use MakinaCorpus\QueryBuilder\Query\Merge;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Query\Update;
use MakinaCorpus\QueryBuilder\Type\InternalType;
use MakinaCorpus\QueryBuilder\Type\Type;
use MakinaCorpus\QueryBuilder\Type\TypeConverter;
use MakinaCorpus\QueryBuilder\Writer\Writer;
use MakinaCorpus\QueryBuilder\Writer\WriterContext;

/**
 * MySQL <= 5.7.
 */
class MySQLWriter extends Writer
{
    #[\Override]
    protected function createTypeConverter(): TypeConverter
    {
        return new MySQLTypeConverter();
    }

    /**
     * MySQL aggregate function names seems to be keywords, not functions.
     */
    #[\Override]
    protected function shouldEscapeAggregateFunctionName(): bool
    {
        return false;
    }

    #[\Override]
    protected function formatCurrentTimestamp(CurrentTimestamp $expression, WriterContext $context): string
    {
        return 'now()';
    }

    #[\Override]
    protected function formatAggregate(Aggregate $expression, WriterContext $context): string
    {
        return $this->doFormatAggregateWithoutFilter($expression, $context);
    }

    #[\Override]
    protected function formatStringHash(StringHash $expression, WriterContext $context): string
    {
        $algo = $expression->getAlgo();
        $value = $this->toText($expression->getValue(), $context);

        return match (\strtolower($algo)) {
            'md5' => 'md5(' . $this->format($value, $context) . ')',
            'sha1' => 'sha1(' . $this->format($value, $context) . ')',
            'sha2' => 'sha2(' . $this->format($value, $context) . ')',
            default => throw new QueryBuilderError("Unsupported arbitrary user given algorithm for MySQL."),
        };
    }

    #[\Override]
    protected function formatCurrentDatabase(CurrentDatabase $expression, WriterContext $context): string
    {
        return 'DATABASE()';
    }

    #[\Override]
    protected function formatCurrentSchema(CurrentSchema $expression, WriterContext $context): string
    {
        return "'public'";
    }

    #[\Override]
    protected function formatDateIntervalUnit(DateIntervalUnit $expression, WriterContext $context, bool $negate = false): string
    {
        return $this->format($expression->getValue(), $context) . ' ' . $expression->getUnit();
    }

    #[\Override]
    protected function formatDateAdd(DateAdd $expression, WriterContext $context): string
    {
        $interval = $expression->getInterval();

        if ($interval instanceof DateInterval) {
            $ret = $this->format($expression->getDate(), $context);

            foreach ($interval->getValues() as $unit) {
                $intervalStr = $this->formatDateIntervalUnit($unit, $context);

                $ret = 'date_add(' . $ret . ', interval ' . $intervalStr . ')';
            }
        } else {
            $ret = 'date_add(' . $this->format($expression->getDate(), $context) . ', ' . $this->format($expression->getInterval(), $context) . ')';
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
                $intervalStr = $this->formatDateIntervalUnit($unit, $context);

                $ret = 'date_sub(' . $ret . ', interval ' . $intervalStr . ')';
            }
        } else {
            $ret = 'date_sub(' . $this->format($expression->getDate(), $context) . ', ' . $this->format($expression->getInterval(), $context) . ')';
        }

        return $ret;
    }

    #[\Override]
    protected function formatDateInterval(DateInterval $expression, WriterContext $context): string
    {
        throw new UnsupportedFeatureError('MySQL does not know the interval type.');
    }

    /**
     * MySQL does not support OFFSET alone.
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
            return 'limit 18446744073709551610 offset ' . $offset;
        }
        return '';
    }

    #[\Override]
    protected function doFormatInsertNoValuesStatement(WriterContext $context): string
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
     */
    #[\Override]
    protected function formatMerge(Merge $query, WriterContext $context): string
    {
        $output = [];

        $columns = $query->getAllColumns();
        $isIgnore = Query::CONFLICT_IGNORE === $query->getConflictBehaviour();
        $table = $query->getTable();

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

        $using = $query->getQuery();
        if ($using instanceof ConstantTable) {
            $output[] = $this->doFormatConstantTable($using, $context, null, true);
        } else {
            $output[] = $this->format($using, $context);
        }

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

    #[\Override]
    protected function formatDelete(Delete $query, WriterContext $context): string
    {
        $output = [];

        // MySQL need to specify on which table to delete from if there is an
        // alias on the main table, so we are going to give him this always
        // so we won't have to bother about weither or not we have other tables
        // to JOIN.
        $table = $query->getTable();
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

    #[\Override]
    protected function formatUpdate(Update $query, WriterContext $context): string
    {
        $output = [];

        $columns = $query->getUpdatedColumns();
        if (empty($columns)) {
            throw new QueryBuilderError("cannot run an update query without any columns to update");
        }

        // @todo For MySQL <8, convert WITH as JOIN statements.
        $output[] = $this->doFormatWith($context, $query->getAllWith());

        // From the SQL 92 standard (which PostgreSQL does support here) the
        // FROM and JOIN must be written AFTER the SET clause. MySQL does not.
        $table = $query->getTable();
        $output[] = 'update ' . $this->format($table, $context);

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
        // MySQL may UPDATE in more than one table at once, hence when you
        // specify a column name in set, it will raise an "Column 'foo' in
        // field list is ambiguous" when you update a column which has the
        // same name as any other columns in JOIN'ed tables.
        // This method will forcefully prefix all SET column names using the
        // UPDATE'd table name.
        if ($from || $join) {
            $output[] = 'set ' . $this->doFormatUpdateSetWithTableName(
                $context,
                $table->getAlias() ?? $table->getName(),
                $columns
            ) . "\n";
        } else {
            $output[] = 'set ' . $this->doFormatUpdateSet($context, $columns) . "\n";
        }

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = 'where ' . $this->formatWhere($where, $context);
        }

        $return = $query->getAllReturn();
        if ($return) {
            throw new QueryBuilderError("MySQL does not support RETURNING SQL clause");
        }

        return \implode("\n", $output);
    }

    /**
     * Format a single set clause (update queries).
     */
    protected function doFormatUpdateSetWithTableNameItem(
        WriterContext $context,
        string $tableName,
        string $columnName,
        string|Expression $expression
    ): string {
        $columnString = $this->escaper->escapeIdentifier($tableName) . '.' . $this->escaper->escapeIdentifier($columnName);

        if ($expression instanceof Expression) {
            return $columnString . ' = ' . $this->format($expression, $context, true);
        }
        return $columnString . ' = ' . $this->escaper->escapeLiteral($expression);
    }

    /**
     * Format all set clauses (update queries).
     *
     * @param string[]|Expression[] $columns
     *   Keys are column names, values are strings or Expression instances
     */
    protected function doFormatUpdateSetWithTableName(WriterContext $context, string $tableName, array $columns): string
    {
        $inner = '';
        foreach ($columns as $column => $value) {
            if ($inner) {
                $inner .= ",\n";
            }
            $inner .= $this->doFormatUpdateSetWithTableNameItem($context, $tableName, $column, $value);
        }
        return $inner;
    }

    /**
     * Compute MySQL cast type.
     *
     * Because MySQL doesn't like consistency and standard, they need specific
     * expressions for CAST instead of usual type names.
     */
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
        if (\in_array($type->internal, [InternalType::FLOAT, InternalType::FLOAT_BIG, InternalType::FLOAT_SMALL, InternalType::DECIMAL])) {
            return 'DECIMAL';
        }
        return null;
    }

    /**
     * MySQL and types, seriously. Be conservative and fix user basic
     * errors, but do not attempt to do too much magic and let unknown
     * types pass.
     *
     * @see https://dev.mysql.com/doc/refman/8.2/en/cast-functions.html#function_cast
     */
    #[\Override]
    protected function doFormatCastExpression(string $expressionString, string|Type $type, WriterContext $context): string
    {
        $type = Type::create($type);

        $typeString = $this->doFormatCastType($type, $context);
        if (null === $typeString) {
            $typeString = $this->typeConverter->getSqlTypeName($type);
        }

        // This will not work with MySQL 5.7 and previous, beware.
        if ($type->array) {
            $typeString .= ' ARRAY';
        }

        return 'CAST(' . $expressionString . ' AS ' . $typeString . ')';
    }

    #[\Override]
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

    #[\Override]
    protected function formatRandom(Random $expression, WriterContext $context): string
    {
        return 'rand()';
    }

    /**
    #[\Override]
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
