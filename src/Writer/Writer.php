<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Writer;

use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Error\UnsupportedExpressionError;
use MakinaCorpus\QueryBuilder\Escaper\Escaper;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Expression\Aggregate;
use MakinaCorpus\QueryBuilder\Expression\Aliased;
use MakinaCorpus\QueryBuilder\Expression\ArrayValue;
use MakinaCorpus\QueryBuilder\Expression\Between;
use MakinaCorpus\QueryBuilder\Expression\CaseWhen;
use MakinaCorpus\QueryBuilder\Expression\Cast;
use MakinaCorpus\QueryBuilder\Expression\Castable;
use MakinaCorpus\QueryBuilder\Expression\ColumnAll;
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\Comparison;
use MakinaCorpus\QueryBuilder\Expression\Concat;
use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Expression\CurrentDatabase;
use MakinaCorpus\QueryBuilder\Expression\CurrentSchema;
use MakinaCorpus\QueryBuilder\Expression\CurrentTimestamp;
use MakinaCorpus\QueryBuilder\Expression\DataType;
use MakinaCorpus\QueryBuilder\Expression\DateAdd;
use MakinaCorpus\QueryBuilder\Expression\DateInterval;
use MakinaCorpus\QueryBuilder\Expression\DateIntervalUnit;
use MakinaCorpus\QueryBuilder\Expression\DateSub;
use MakinaCorpus\QueryBuilder\Expression\FunctionCall;
use MakinaCorpus\QueryBuilder\Expression\Identifier;
use MakinaCorpus\QueryBuilder\Expression\IfThen;
use MakinaCorpus\QueryBuilder\Expression\LikePattern;
use MakinaCorpus\QueryBuilder\Expression\Lpad;
use MakinaCorpus\QueryBuilder\Expression\Modulo;
use MakinaCorpus\QueryBuilder\Expression\Not;
use MakinaCorpus\QueryBuilder\Expression\NullValue;
use MakinaCorpus\QueryBuilder\Expression\Random;
use MakinaCorpus\QueryBuilder\Expression\RandomInt;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Row;
use MakinaCorpus\QueryBuilder\Expression\Rpad;
use MakinaCorpus\QueryBuilder\Expression\SimilarToPattern;
use MakinaCorpus\QueryBuilder\Expression\StringHash;
use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Expression\Window;
use MakinaCorpus\QueryBuilder\Expression\WithAlias;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Query\Delete;
use MakinaCorpus\QueryBuilder\Query\Insert;
use MakinaCorpus\QueryBuilder\Query\Merge;
use MakinaCorpus\QueryBuilder\Query\Partial\JoinStatement;
use MakinaCorpus\QueryBuilder\Query\Partial\OrderByStatement;
use MakinaCorpus\QueryBuilder\Query\Partial\SelectColumn;
use MakinaCorpus\QueryBuilder\Query\Partial\WithStatement;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Query\RawQuery;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Query\Update;
use MakinaCorpus\QueryBuilder\SqlString;
use MakinaCorpus\QueryBuilder\Type\Type;
use MakinaCorpus\QueryBuilder\Type\TypeConverter;
use MakinaCorpus\QueryBuilder\Where;

/**
 * Standard SQL query formatter: this implementation conforms as much as it
 * can to SQL-92 standard, and higher revisions for some functions.
 *
 * Please note that the main target is PostgreSQL, and PostgreSQL has an
 * excellent SQL-92|1999|2003|2006|2008|2011 standard support, almost
 * everything in here except MERGE queries is supported by PostgreSQL.
 *
 * We could have override the CastExpression formatting for PostgreSQL using
 * its ::TYPE shorthand, but since that is is only a legacy syntax shorthand
 * and that the standard CAST(value AS type) expression may require less
 * parenthesis in some cases, it's simply eaiser to keep the CAST() syntax.
 *
 * All methods starting with "format" do handle a known Expression class,
 * whereas all methods starting with "do" will handle an internal behaviour.
 */
class Writer
{
    private string $matchParametersRegex;
    protected ?TypeConverter $typeConverter = null;
    private ?Converter $converter = null;
    protected Escaper $escaper;

    public function __construct(?Escaper $escaper = null, ?Converter $converter = null)
    {
        $this->converter = $converter;
        $this->escaper = $escaper ?? new StandardEscaper();
        $this->typeConverter = $this->createTypeConverter();
        $this->buildParameterRegex();
    }

    /**
     * Get type converter.
     */
    public function getTypeConverter(): TypeConverter
    {
        return $this->typeConverter;
    }

    /**
     * Create vendor-specific type converter.
     */
    protected function createTypeConverter(): TypeConverter
    {
        return new TypeConverter();
    }

    /**
     * Get converter.
     */
    protected function getConverter(): Converter
    {
        return $this->converter ??= new Converter();
    }

    /**
     * Create SQL text from given expression.
     */
    public function prepare(string|Expression|SqlString $sql): SqlString
    {
        if ($sql instanceof SqlString) {
            return $sql;
        }

        $identifier = null;

        if (\is_string($sql)) {
            $sql = new Raw($sql);
        } else {
            // if with identifier
            //   then $identifier = $sql->getIdentifier();
        }

        $context = new WriterContext($this->getConverter());
        $rawSql = $this->format($sql, $context);

        return new SqlString(
            $rawSql,
            $context->getArgumentBag(),
            $identifier,
            $sql instanceof Query ? $sql->getOptions() : null,
        );
    }

    /**
     * Generic force cast when expression type is unknown or is not compatible
     * with given target type.
     *
     * In numerous cases, this API will add explicit CAST() calls in order to
     * bypass some wrong type guess due to parameters usage when querying.
     */
    protected function forceCast(Expression $expression, WriterContext $context, Type $type, bool $ignoreRaw = true): Expression
    {
        // Identifiers and function call are typed on the RDBMS side, so we
        // should never automatically cast. The user needs to take care of
        // sane type conversion itself.
        if ($expression instanceof Identifier || $expression instanceof FunctionCall) {
            return $expression;
        }
        if ($ignoreRaw && $expression instanceof Raw) {
            return $expression;
        }

        $castType = null;
        $willCast = $expression instanceof Castable && ($castType = $expression->getCastToType());

        if ($willCast && $this->typeConverter->isCompatible($type, $castType)) {
            return $expression;
        }

        // This opiniated, but we always force value cast per default because
        // when prepared, value types are unknown to the database server, which
        // prevent it from doing proper query optimization.
        // Some will simply ignore this and will not optimize for example MySQL
        // which does not seem to optimize anything at all. But some other will
        // crash because of type ambiguity at PREPARE time such as PostgreSQL,
        // which is the sane behaviour we would expect.
        $returnType = $castType ?? $expression->returnType();

        if ($expression instanceof Value || !$returnType || !$this->typeConverter->isCompatible($type, $returnType)) {
            return new Cast($expression, $type);
        }

        return $expression;
    }

    /**
     * Force text cast when type is not compatible.
     */
    protected function toText(Expression $expression, WriterContext $context, bool $ignoreRaw = true): Expression
    {
        return $this->forceCast($expression, $context, Type::varchar(), $ignoreRaw);
    }

    /**
     * Force integer cast when type is not compatible.
     */
    protected function toInt(Expression $expression, WriterContext $context, bool $ignoreRaw = true): Expression
    {
        return $this->forceCast($expression, $context, Type::int(), $ignoreRaw);
    }

    /**
     * Force timestamp cast when type is not compatible.
     */
    protected function toDate(Expression $expression, WriterContext $context, bool $ignoreRaw = true): Expression
    {
        return $this->forceCast($expression, $context, Type::timestamp(), $ignoreRaw);
    }

    /**
     * Do format expression.
     */
    protected function format(Expression $expression, WriterContext $context, bool $enforceParenthesis = false): string
    {
        // Queries may be overriden by bridges to add functionality.
        if ($expression instanceof Query) {
            if ($expression instanceof Delete) {
                $ret = $this->formatDelete($expression, $context);
            } else if ($expression instanceof Insert) {
                $ret = $this->formatInsert($expression, $context);
            } else if ($expression instanceof Merge) {
                $ret = $this->formatMerge($expression, $context);
            } else if ($expression instanceof RawQuery) {
                $ret = $this->formatRawQuery($expression, $context);
            } else if ($expression instanceof Select) {
                $ret = $this->formatSelect($expression, $context);
            } else if ($expression instanceof Update) {
                $ret = $this->formatUpdate($expression, $context);
            } else {
                throw new UnsupportedExpressionError(\sprintf("Unexpected expression object type: %s", \get_class($expression)));
            }
        } else if ($expression instanceof Where) {
            $ret = $this->formatWhere($expression, $context);
        } else {
            try {
                $ret = match (\get_class($expression)) {
                    Aggregate::class => $this->formatAggregate($expression, $context),
                    Aliased::class => $this->formatAliased($expression, $context),
                    ArrayValue::class => $this->formatArrayValue($expression, $context),
                    Between::class => $this->formatBetween($expression, $context),
                    CaseWhen::class => $this->formatCaseWhen($expression, $context),
                    Cast::class => $this->formatCast($expression, $context),
                    ColumnAll::class => $this->formatColumnAll($expression, $context),
                    ColumnName::class => $this->formatIdentifier($expression, $context),
                    Comparison::class => $this->formatComparison($expression, $context),
                    Concat::class => $this->formatConcat($expression, $context),
                    ConstantTable::class => $this->formatConstantTable($expression, $context),
                    CurrentDatabase::class => $this->formatCurrentDatabase($expression, $context),
                    CurrentSchema::class => $this->formatCurrentSchema($expression, $context),
                    CurrentTimestamp::class => $this->formatCurrentTimestamp($expression, $context),
                    DataType::class => $this->formatDataType($expression, $context),
                    DateAdd::class => $this->formatDateAdd($expression, $context),
                    DateInterval::class => $this->formatDateInterval($expression, $context),
                    DateIntervalUnit::class => $this->formatDateIntervalUnit($expression, $context),
                    DateSub::class => $this->formatDateSub($expression, $context),
                    Identifier::class => $this->formatIdentifier($expression, $context),
                    IfThen::class => $this->formatIfThen($expression, $context),
                    LikePattern::class => $this->formatLikePattern($expression, $context),
                    Lpad::class => $this->formatLpad($expression, $context),
                    Modulo::class => $this->formatModulo($expression, $context),
                    Not::class => $this->formatNot($expression, $context),
                    NullValue::class => $this->formatNullValue($expression, $context),
                    Random::class => $this->formatRandom($expression, $context),
                    RandomInt::class => $this->formatRandomInt($expression, $context),
                    Raw::class => $this->formatRaw($expression, $context),
                    Row::class => $this->formatRow($expression, $context),
                    Rpad::class => $this->formatRpad($expression, $context),
                    SimilarToPattern::class => $this->formatSimilarToPattern($expression, $context),
                    StringHash::class => $this->formatStringHash($expression, $context),
                    TableName::class => $this->formatIdentifier($expression, $context),
                    Value::class => $this->formatValue($expression, $context),
                    Window::class => $this->formatWindow($expression, $context),
                    default => throw new UnsupportedExpressionError(\sprintf("Unexpected expression object type: %s", \get_class($expression))),
                };
            } catch (UnsupportedExpressionError $e) {
                if ($expression instanceof FunctionCall) {
                    $ret = $this->formatFunctionCall($expression, $context);
                } else if ($expression instanceof Comparison) {
                    $ret = $this->formatComparison($expression, $context);
                } else {
                    throw $e;
                }
            }
        }

        // Working with Aliased special case, we need to write parenthesis
        // depending upon the decorated expression, not the Aliased one.
        if (!$expression instanceof Aliased && $expression instanceof WithAlias && ($alias = $expression->getAlias())) {
            if ($this->expressionRequiresParenthesis($expression)) {
                return '(' . $ret . ') as ' . $this->escaper->escapeIdentifier($alias);
            }
            return $ret . ' as ' . $this->escaper->escapeIdentifier($alias);
        }

        if ($enforceParenthesis && $this->expressionRequiresParenthesis($expression)) {
            return '(' . $ret . ')';
        }

        return $ret;
    }

    /**
     * Does expression requires parenthesis when used inside another expression.
     */
    protected function expressionRequiresParenthesis(Expression $expression): bool
    {
        return
            $expression instanceof ConstantTable ||
            $expression instanceof RawQuery ||
            $expression instanceof Select ||
            $expression instanceof Where
        ;
    }

    /**
     * Converts all typed placeholders in the query and replace them with the
     * correct placeholder syntax. It matches ? or ?::TYPE syntaxes, outside of
     * SQL dialect escape sequences, everything else will be left as-is.
     *
     * Real conversion is done later in the runner implementation, when it
     * reconciles the user given arguments with the type information found while
     * formating or parsing the SQL.
     */
    protected function parseExpression(Raw|RawQuery $expression, WriterContext $context): string
    {
        $asString = $expression->getString();
        $values = $expression->getArguments();

        if (!$values && !\str_contains($asString, '?')) {
            // Performance shortcut for expressions containing no arguments.
            return $asString;
        }

        $converter = $this->getConverter();

        // See https://stackoverflow.com/a/3735908 for the  starting
        // sequence explaination, the rest should be comprehensible.
        $localIndex = -1;
        return \preg_replace_callback(
            $this->matchParametersRegex,
            function ($matches) use (&$localIndex, $values, $context, $converter) {
                $match  = $matches[0];

                if ('??' === $match) {
                    return $this->escaper->unescapePlaceholderChar();
                }
                if ('?' !== $match[0]) {
                    return $match;
                }

                $localIndex++;
                $value = $values[$localIndex] ?? null;

                return $this->format(
                    $converter->toExpression($value, $matches[6] ?? null),
                    $context
                );
            },
            $asString
        );
    }

    /**
     * Format a single set clause (update queries).
     */
    protected function doFormatUpdateSetItem(WriterContext $context, string $columnName, string|Expression $expression): string
    {
        $columnString = $this->escaper->escapeIdentifier($columnName);

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
    protected function doFormatUpdateSet(WriterContext $context, array $columns): string
    {
        $inner = '';
        foreach ($columns as $column => $value) {
            if ($inner) {
                $inner .= ",\n";
            }
            $inner .= $this->doFormatUpdateSetItem($context, $column, $value);
        }
        return $inner;
    }

    /**
     * Format projection for a single select column or statement.
     *
     * @param null|string $escapedTableName
     *   If given, all expressions that are identified as referencing a column
     *   name without a table name will be prefixed with this raw table name
     *   string.
     *   Warning, this will not recurse into more complex expression, mostly
     *   for performance purpose.
     */
    protected function doFormatSelectItem(WriterContext $context, SelectColumn $column, ?string $escapedTableName = null): string
    {
        $expression = $column->getExpression();
        $computed = null;

        // @todo Add a query option to disable this behaviour.
        // In case we have a forced escaped table name to prefix column names
        // with, then proceed to some alteration of the user given input.
        // This will fix the following things:
        //    - * -> <escaped_table_name>.*
        //    - <column_name> -> <escaped_table_name>.<column_name>
        if ($escapedTableName) {
            if ($expression instanceof ColumnAll) {
                if (!$expression->getNamespace()) {
                    $computed = $escapedTableName . '.*';
                }
            } if ($expression instanceof Identifier) {
                if (!$expression->getNamespace()) {
                    $computed = $escapedTableName . '.' . $this->escaper->escapeIdentifier($expression->getName());
                }
            }
        }

        if (!$computed) {
            $computed = $this->format($expression, $context, true);

            // Using a ROW() requires the ROW keyword in order to avoid
            // SQL language ambiguities. All other cases are dealt with
            // the format() method enforcing parenthesis.
            if ($expression instanceof Row) {
                $computed = 'row' . $computed;
            }
        }

        // We cannot alias columns with a numeric identifier;
        // aliasing with the same string as the column name
        // makes no sense either.
        $alias = $column->getAlias();
        if ($alias && !\is_numeric($alias)) {
            $alias = $this->escaper->escapeIdentifier($alias);
            if ($alias !== $computed) {
                return $computed . ' as ' . $alias;
            }
        }

        return $computed;
    }

    /**
     * Format SELECT columns.
     *
     * @param SelectColumn[] $columns
     * @param null|string $escapedTableName
     *   If set, dictate this method to automatically add the given table name
     *   on all items that lacks it. It will server for the SQL Server OUTPUT
     *   clause that require that you give a table for each returned item.
     */
    protected function doFormatSelect(WriterContext $context, array $columns, ?string $escapedTableName = null): string
    {
        if (!$columns) {
            return '*';
        }

        return \implode(
            ",\n",
            \array_map(
                fn ($column) => $this->doFormatSelectItem($context, $column, $escapedTableName),
                $columns,
            )
        );
    }

    /**
     * Format WINDOW at the SELECT level.
     *
     * @param Window[] $windows
     */
    protected function doFormatWindows(WriterContext $context, array $windows): string
    {
        $output = '';
        foreach ($windows as $window) {
            \assert($window instanceof Window);

            $output .= ($output ? ', ' : 'window ')
                . $this->escaper->escapeIdentifier($window->getAlias())
                . " as "
                . $this->format($window, $context)
            ;
        }

        return $output;
    }

    /**
     * Format the whole projection.
     *
     * @param array $return
     *   Each column is an array that must contain:
     *     - 0: string or Statement: column name or SQL statement
     *     - 1: column alias, can be empty or null for no aliasing
     */
    protected function doFormatReturning(WriterContext $context, array $return, ?string $escapedTableName = null): string
    {
        return 'returning ' . $this->doFormatSelect($context, $return);
    }

    /**
     * Format a single order by.
     *
     * @param int $order
     *   Query::ORDER_* constant.
     * @param int $null
     *   Query::NULL_* constant.
     */
    protected function doFormatOrderByItem(WriterContext $context, string|Expression $column, int $order, int $null): string
    {
        $column = $this->format($column, $context);

        if (Query::ORDER_ASC === $order) {
            $orderStr = 'asc';
        } else {
            $orderStr = 'desc';
        }

        return $column . ' ' . $orderStr . match ($null) {
            Query::NULL_FIRST => ' nulls first',
            Query::NULL_LAST => ' nulls last',
            default => '',
        };
    }

    /**
     * Format the whole order by clause.
     *
     * @todo Convert $orders items to an Order class.
     *
     * @param OrderByStatement[] $orders
     */
    protected function doFormatOrderBy(WriterContext $context, array $orders): string
    {
        if (!$orders) {
            return '';
        }

        $output = [];

        foreach ($orders as $order) {
            \assert($order instanceof OrderByStatement);
            $output[] = $this->doFormatOrderByItem($context, $order->column, $order->order, $order->null);
        }

        return "order by " . \implode(", ", $output);
    }

    /**
     * Format the whole group by clause.
     *
     * @param Expression[] $groups
     *   Array of column names or aliases.
     */
    protected function doFormatGroupBy(WriterContext $context, array $groups): string
    {
        if (!$groups) {
            return '';
        }

        $output = [];
        foreach ($groups as $group) {
            $output[] = $this->format($group, $context, true);
        }

        return "group by " . \implode(", ", $output);
    }

    /**
     * Format a single join statement.
     */
    protected function doFormatJoinItem(WriterContext $context, JoinStatement $join): string
    {
        $prefix = match ($mode = $join->mode) {
            Query::JOIN_NATURAL => 'natural join',
            Query::JOIN_LEFT => 'left outer join',
            Query::JOIN_LEFT_OUTER => 'left outer join',
            Query::JOIN_RIGHT => 'right outer join',
            Query::JOIN_RIGHT_OUTER => 'right outer join',
            Query::JOIN_INNER => 'inner join',
            default => $mode,
        };

        if ($join->condition->isEmpty()) {
            // When there is no conditions, CROSS JOIN must be applied.
            // @todo Should we raise an error if join mode is not the default?
            return 'cross join ' . $this->format($join->table, $context, true);
        }
        return $prefix . ' ' . $this->format($join->table, $context, true) . ' on (' . $this->format($join->condition, $context, false) . ')';
    }

    /**
     * Format all join statements.
     *
     * @param JoinStatement[] $join
     */
    protected function doFormatJoin(
        WriterContext $context,
        array $join,
        bool $transformFirstJoinAsFrom = false,
        ?string $fromPrefix = null,
        Query $query = null
    ): string {
        if (!$join) {
            return '';
        }

        $output = [];

        if ($transformFirstJoinAsFrom) {
            $first = \array_shift($join);
            \assert($first instanceof JoinStatement);

            // First join must be an inner join, there is no choice, and first join
            // condition will become a where clause in the global query instead
            if (!\in_array($first->mode, [Query::JOIN_INNER, Query::JOIN_NATURAL])) {
                throw new QueryBuilderError("First join in an update query must be inner or natural, it will serve as the first FROM or USING table.");
            }

            if ($fromPrefix) {
                $output[] = $fromPrefix . ' ' . $this->format($first->table, $context, true);
            } else {
                $output[] = $this->format($first->table, $context, true);
            }

            if (!$first->condition->isEmpty()) {
                if (!$query) {
                    throw new QueryBuilderError("Something very bad happened.");
                }
                // @phpstan-ignore-next-line
                $query->getWhere()->raw($first->condition);
            }
        }

        foreach ($join as $item) {
            $output[] = $this->doFormatJoinItem($context, $item);
        }

        return \implode("\n", $output);
    }

    /**
     * Format all update from statement.
     *
     * @param Expression[] $from
     */
    protected function doFormatFrom(WriterContext $context, array $from, ?string $prefix): string
    {
        if (!$from) {
            return '';
        }

        $output = [];

        foreach ($from as $item) {
            \assert($item instanceof Expression);

            $itemOutput = $this->format($item, $context, true);

            if ($item instanceof ConstantTable) {
                if ($columnAliases = $item->getColumns()) {
                    $itemOutput .= ' (' . $this->doFormatColumnNameList($context, $columnAliases) . ')';
                }
            }

            $output[] = $itemOutput;
        }

        return ($prefix ? $prefix . ' ' : '') . \implode(', ', $output);
    }

    /**
     * When no values are set in an insert query, what should we write?
     */
    protected function doFormatInsertNoValuesStatement(WriterContext $context): string
    {
        return "DEFAULT VALUES";
    }

    /**
     * Format array of with statements.
     *
     * @param WithStatement[] $with
     */
    protected function doFormatWith(WriterContext $context, array $with): string
    {
        if (!$with) {
            return '';
        }

        $output = [];
        foreach ($with as $item) {
            \assert($item instanceof WithStatement);
            $expression = $item->getExpression();

            // @todo I don't think I can do better than that, but I'm really sorry.
            if ($expression instanceof ConstantTable && ($columnAliases = $expression->getColumns())) {
                $output[] = $this->escaper->escapeIdentifier($item->getAlias()) . ' (' . $this->doFormatColumnNameList($context, $columnAliases) . ') as (' . $this->format($expression, $context) . ')';
            } else {
                $output[] = $this->escaper->escapeIdentifier($item->getAlias()) . ' as (' . $this->format($expression, $context) . ')';
            }
        }

        return 'with ' . \implode(', ', $output);
    }

    /**
     * Format range statement.
     *
     * @param int $limit
     *   O means no limit.
     * @param int $offset
     *   0 means default offset.
     */
    protected function doFormatRange(WriterContext $context, int $limit = 0, int $offset = 0, bool $hasOrder = true): string
    {
        if ($limit) {
            if (!$offset) {
                return 'limit ' . $limit;
            }
            return 'limit ' . $limit . ' offset ' . $offset;
        }
        if ($offset) {
            return 'offset ' . $offset;
        }
        return '';
    }

    /**
     * Format a column name list.
     */
    protected function doFormatColumnNameList(WriterContext $context, array $columnNames): string
    {
        return \implode(
            ', ',
            \array_map(
                fn ($column) => $this->escaper->escapeIdentifier($column),
                $columnNames
            )
        );
    }

    /**
     * Format data type declaration.
     */
    protected function formatDataType(DataType $expression, WriterContext $context): string
    {
        $type = $expression->getDataType();

        $prefix = '';
        if ($type->unsigned) {
            $prefix .= 'unsigned ';
        }

        $suffix = '';
        if ($type->length) {
            $suffix .= '(' . $type->length . ')';
        } else if ($type->precision && $type->scale) {
            $suffix .= '(' . $type->precision . ',' . $type->scale . ')';
        }
        if ($type->array) {
            $suffix .= '[]';
        }

        return $prefix . $this->typeConverter->getSqlTypeName($type) . $suffix;
    }

    /**
     * Format a CASE WHEN .. THEN .. ELSE .. statement.
     */
    protected function formatCaseWhen(CaseWhen $expression, WriterContext $context): string
    {
        $output = '';

        foreach ($expression->getCases() as $case) {
            \assert($case instanceof IfThen);
            $output .= "\n when " . $this->format($case->getCondition(), $context) . ' then ' . $this->format($case->getThen(), $context);
        }

        $else = $expression->getElse();

        if (!$output) {
            return $this->format($else, $context);
        }

        return 'case ' . $output . ' else ' . $this->format($else, $context) . ' end';
    }

    /**
     * Format a function call.
     */
    protected function formatFunctionCall(FunctionCall $expression, WriterContext $context): string
    {
        $name = $expression->getName();

        if (!\ctype_alnum($name)) {
            $name = $this->escaper->escapeIdentifier($name);
        }

        $inner = '';
        foreach ($expression->getArguments() as $argument) {
            if ($inner) {
                $inner .= ', ';
            }
            $inner .= $this->format($argument, $context);
        }

        return $name . '(' . $inner . ')';
    }

    /**
     * Format a function call.
     */
    protected function formatConcat(Concat $expression, WriterContext $context): string
    {
        $output = '';
        foreach ($expression->getArguments() as $argument) {
            if ($output) {
                $output .= ' || ';
            }
            $output .= $this->format($argument, $context);
        }

        return $output;
    }

    /**
     * Format string hash.
     *
     * @see https://modern-sql.com/caniuse/MD5-algorithm
     *   There is no generic HASH() function, we have to implement this on
     *   a per-backend basis. For unit tests, we implement the PostgreSQL
     *   variant here.
     * @see https://www.postgresql.org/docs/current/pgcrypto.html
     */
    protected function formatStringHash(StringHash $expression, WriterContext $context): string
    {
        $algo = $expression->getAlgo();
        $escapedAlgo = $this->escaper->escapeLiteral($algo);
        $value = $this->toText($expression->getValue(), $context);

        return match (\strtolower($algo)) {
            'md5' => 'md5(' . $this->format($value, $context) . ')',
            default => 'digest(' . $this->format($value, $context) . ', ' . $escapedAlgo . ')',
        };
    }

    /**
     * Current database. No standard here, only dialects.
     */
    protected function formatCurrentDatabase(CurrentDatabase $expression, WriterContext $context): string
    {
        return 'CURRENT_DATABASE()';
    }

    /**
     * Current schema. No standard here, only dialects.
     */
    protected function formatCurrentSchema(CurrentSchema $expression, WriterContext $context): string
    {
        return 'CURRENT_SCHEMA()';
    }

    /**
     * CURRENT_TIMESTAMP, NOW(), GETDATE() depending upon the dialect.
     */
    protected function formatCurrentTimestamp(CurrentTimestamp $expression, WriterContext $context): string
    {
        return 'current_timestamp';
    }

    /**
     * Format an IF .. THEN .. ELSE .. statement.
     *
     * Default implementation is formatting as a CASE .. WHEN expression,
     * it works on all officially supported RDBMS.
     */
    protected function formatIfThen(IfThen $expression, WriterContext $context): string
    {
        return $this->formatCaseWhen($expression->toCaseWhen(), $context);
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

        // This is weird one, PostgreSQL when used over PDO or doctrine/dbal
        // is unable to discover the second parameter type, which makes the
        // "-" operator being undetermined, because it exists for more than
        // one type.
        // Since CAST() is standard SQL and supported by everyone, we will
        // leave it here. In case of any problem with it, please file an
        // issue, and this code will move into the PostgreSQL specific
        // implementation.
        // I have no certitude here, but it may be because PDO issues real
        // PREPARE statements, at some point, and that PostgreSQL optimises
        // the query prior to having the real parameter values, which means
        // that it doesn't know the user input will be an integer.
        return $this->formatRaw(
            new Raw(
                'FLOOR(? * (? - ? + 1) + ?)',
                [new Random(), new Cast($max, Type::int()), $min, $min]
            ),
            $context,
        );
    }

    /**
     * Format negation of another expression.
     */
    protected function formatNot(Not $expression, WriterContext $context): string
    {
        $innerExpression = $expression->getExpression();

        return 'not ' . $this->format($innerExpression, $context, true);
    }

    /**
     * Format generic comparison expression.
     */
    protected function formatComparison(Comparison $expression, WriterContext $context): string
    {
        $output = '';

        $left = $expression->getLeft();
        $right = $expression->getRight();
        $operator = $expression->getOperator();

        if ($left) {
            $output .= $this->format($left, $context, true);
        }

        if ($operator) {
            $output .= ' ' . $operator;
        }

        if ($right) {
            $output .= ' ' . $this->format($right, $context, true);
        }

        return $output;
    }

    /**
     * Format BETWEEN expression.
     */
    protected function formatBetween(Between $expression, WriterContext $context): string
    {
        $column = $expression->getColumn();
        $from = $expression->getFrom();
        $to = $expression->getTo();

        return $this->format($column, $context) . ' between ' . $this->format($from, $context) . ' and ' . $this->format($to, $context);
    }

    /**
     * Format date addition.
     */
    protected function formatDateAdd(DateAdd $expression, WriterContext $context): string
    {
        return $this->format($this->toDate($expression->getDate(), $context), $context) . ' + ' . $this->format($expression->getInterval(), $context);
    }

    /**
     * Format date substraction.
     */
    protected function formatDateSub(DateSub $expression, WriterContext $context): string
    {
        return $this->format($this->toDate($expression->getDate(), $context), $context) . ' - ' . $this->format($expression->getInterval(), $context);
    }

    /**
     * Format a single date interval unit.
     *
     * This method should never be called outside of the formatDateInterval()
     * method. Writing such interval makes no sense alone.
     */
    protected function formatDateIntervalUnit(DateIntervalUnit $expression, WriterContext $context, bool $negate = false): string
    {
        if ($negate) {
            $prefix = '(0 - ' . $this->format($expression->getValue(), $context) . ')';
        } else {
            $prefix = $this->format($expression->getValue(), $context);
        }
        return $prefix . " || ' ' || " . $this->formatValue(new Value($expression->getUnit(), Type::text()), $context);
    }

    /**
     * Format date interval.
     */
    protected function formatDateInterval(DateInterval $expression, WriterContext $context): string
    {
        $interval = '';

        $first = true;
        foreach ($expression->getValues() as $unit) {
            if ($first) {
                $first = false;
            } else {
                $interval .= " || ' ' || ";
            }
            $interval .= $this->formatDateIntervalUnit($unit, $context);
        }

        return 'cast(' . $interval . ' as interval)';
    }

    protected function doGetPadArguments(Lpad $expression, WriterContext $context): array
    {
        return [
            $this->toText($expression->getValue(), $context),
            $this->toInt($expression->getSize(), $context),
            $this->toText($expression->getFill(), $context),
        ];
    }

    /**
     * Format left pad expression.
     */
    protected function formatLpad(Lpad $expression, WriterContext $context): string
    {
        list($value, $size, $fill) = $this->doGetPadArguments($expression, $context);

        return 'lpad(' . $this->format($value, $context) . ', ' . $this->format($size, $context) . ', ' . $this->format($fill, $context) . ')';
    }

    /**
     * Format right pad expression.
     */
    protected function formatRpad(Lpad $expression, WriterContext $context): string
    {
        list($value, $size, $fill) = $this->doGetPadArguments($expression, $context);

        return 'rpad(' . $this->format($value, $context) . ', ' . $this->format($size, $context) . ', ' . $this->format($fill, $context) . ')';
    }

    /**
     * Format modulo expression.
     */
    protected function formatModulo(Modulo $expression, WriterContext $context): string
    {
        // Here we force CAST(? AS int) because parametrized value type will
        // be unknown to the server when preparing the SQL query, which will
        // raise error because modulo operator might exist for more than one
        // type.
        return $this->format($this->toInt($expression->getLeft(), $context, true), $context) . ' % ' . $this->format($expression->getRight(), $context);
    }

    /**
     * Format where instance.
     */
    protected function formatWhere(Where $expression, WriterContext $context): string
    {
        if ($expression->isEmpty()) {
            // Definitely legit, except for PostgreSQL which seems to require
            // a boolean value for those expressions. In theory, booleans are
            // part of the SQL standard, but a lot of RDBMS don't support them,
            // so we keep the "1" here.
            return '1';
        }

        $output = '';
        $operator = $expression->getOperator();

        foreach ($expression->getConditions() as $expression) {
            // Do not allow an empty where to be displayed.
            if ($expression instanceof Where && $expression->isEmpty()) {
                continue;
            }

            if ($output) {
                $output .= "\n" . $operator . ' ';
            }

            if ($expression instanceof Where || $this->expressionRequiresParenthesis($expression)) {
                $output .= '(' . $this->format($expression, $context) . ')';
            } else {
                $output .= $this->format($expression, $context);
            }
        }

        return $output;
    }

    protected function formatWindow(Window $expression, WriterContext $context): string
    {
        $output = '(';
        if ($partitionBy = $expression->getPartitionBy()) {
            $output .= ' partition by ' . $this->format($partitionBy, $context);
        }
        if ($orderByAll = $expression->getAllOrderBy()) {
            $output .= $this->doFormatOrderBy($context, $orderByAll);
        }
        return $output . ')';
    }

    /**
     * Format a constant table expression when used in INSERT/MERGE.
     *
     * SQL standard uses the SQL standard VALUES constant table expression.
     */
    protected function doFormatValuesInsert(ConstantTable $expression, WriterContext $context, ?string $alias): string
    {
        return $this->doFormatConstantTable($expression, $context, $alias, true);
    }

    /**
     * Format a constant table expression.
     *
     * SQL standard is VALUES (?,?), (?, ?), ... but sadly, MySQL doesn't speak
     * standard SQL, whereas MariaDB has diverged and now does uses the standard
     * variant.
     *
     * This is why the doFormatConstantTableRow() function exists.
     *
     * @see https://www.postgresql.org/docs/current/sql-values.html
     *   PostgreSQL is the nearest thing we could find of standard SQL.
     */
    protected function doFormatConstantTable(ConstantTable $expression, WriterContext $context, ?string $alias, bool $inInsert = false): string
    {
        $inner = null;
        foreach ($expression->getRows() as $row) {
            if ($inner) {
                $inner .= "\n," . $this->doFormatConstantTableRow($row, $context, $inInsert);
            } else {
                $inner = $this->doFormatConstantTableRow($row, $context, $inInsert);
            }
        }

        // Do not add column names if there are no values, otherwise there
        // probably will be a column count mismatch and it will fail.
        // This will output something such as:
        //    VALUES (1, 2), (3, 4) AS "alias" ("column1", "column2").
        // Which is the correct syntax for using a constant table in
        // a FROM clause and name its columns at the same time. This at
        // least works with PostgreSQL.
        if ($inner && $alias) {
            if ($columns = $expression->getColumns()) {
                return "(values " . $inner . ") as " . $this->escaper->escapeIdentifier($alias) . ' (' . $this->doFormatColumnNameList($context, $columns) . ')';
            }
            return "(values " . $inner . ") as " . $this->escaper->escapeIdentifier($alias);
        }

        return "values " . ($inner ?? '()');
    }

    /**
     * Format a constant table row.
     *
     * @see https://www.postgresql.org/docs/current/sql-values.html
     *   PostgreSQL is the nearest thing we could find of standard SQL.
     */
    protected function doFormatConstantTableRow(Row $expression, WriterContext $context, bool $inInsert = false): string
    {
        return $this->formatRow($expression, $context);
    }

    /**
     * Format a constant table expression.
     */
    protected function formatConstantTable(ConstantTable $expression, WriterContext $context): string
    {
        return $this->doFormatConstantTable($expression, $context, null, false);
    }

    /**
     * Format an arbitrary row of values.
     */
    protected function formatRow(Row $expression, WriterContext $context): string
    {
        $inner = null;
        foreach ($expression->getValues() as $value) {
            $local = $this->format($value, $context, true);
            if ($inner) {
                $inner .= ', ' . $local;
            } else {
                $inner = $local;
            }
        }

        if ($expression->shouldCast()) {
            return $this->doFormatCastExpression('(' . $inner . ')', $expression->getCompositeTypeName(), $context);
        }
        return '(' . $inner . ')';
    }

    /**
     * For RETURNING/OUTPUT statements in DELETE/UPDATE/INSERT/MERGE queries
     * identify the NEW version of the mutated row.
     *
     * Default implementation simply returns the table alias, because we were
     * writing this for PostgreSQL at the time, and PostgreSQL doesn't
     * discriminate OLD or NEW row and simply return the new version for all
     * queries but DELETE.
     */
    protected function doFormatOutputNewRowIdentifier(TableName $table): string
    {
        return $this->escaper->escapeIdentifier($table->getAlias() ?? $table->getName());
    }

    /**
     * For RETURNING/OUTPUT statements in DELETE/UPDATE/INSERT/MERGE queries
     * identify the OLD version of the mutated row.
     *
     * Default implementation simply returns the table alias, because we were
     * writing this for PostgreSQL at the time, and PostgreSQL doesn't
     * discriminate OLD or NEW row and simply return the new version for all
     * queries but DELETE.
     */
    protected function doFormatOutputOldRowIdentifier(TableName $table): string
    {
        return $this->escaper->escapeIdentifier($table->getAlias() ?? $table->getName());
    }

    /**
     * Format given merge query.
     */
    protected function formatMerge(Merge $query, WriterContext $context): string
    {
        $output = [];

        $table = $query->getTable();
        $columns = $query->getAllColumns();
        $escapedInsertTable = $this->escaper->escapeIdentifier($table->getName());
        $escapedUsingAlias = $this->escaper->escapeIdentifier($query->getUsingTableAlias());

        $output[] = $this->doFormatWith($context, $query->getAllWith());

        // From SQL:2003 standard, MERGE queries don't have table alias.
        $output[] = "merge into " . $escapedInsertTable;

        // USING
        $using = $query->getQuery();
        if ($using instanceof ConstantTable) {
            $output[] = 'using ' . $this->format($using, $context) . ' as ' . $escapedUsingAlias;
            if ($columnAliases = $using->getColumns()) {
                $output[] = ' (' . $this->doFormatColumnNameList($context, $columnAliases) . ')';
            }
        } else {
            $output[] = 'using (' . $this->format($using, $context) . ') as ' . $escapedUsingAlias;
        }

        // Build USING columns map.
        $usingColumnMap = [];
        foreach ($columns as $column) {
            $usingColumnMap[$column] = $escapedUsingAlias . "." . $this->escaper->escapeIdentifier($column);
        }

        // WHEN MATCHED THEN
        switch ($mode = $query->getConflictBehaviour()) {

            case Query::CONFLICT_IGNORE:
                // Do nothing.
                break;

            case Query::CONFLICT_UPDATE:
                // Exclude primary key from the UPDATE statement.
                $key = $query->getKey();
                $setColumnMap = [];
                foreach ($usingColumnMap as $column => $usingColumnExpression) {
                    if (!\in_array($column, $key)) {
                        $setColumnMap[$column] = new Raw($usingColumnExpression);
                    }
                }
                $output[] = "when matched then update set";
                $output[] = $this->doFormatUpdateSet($context, $setColumnMap);
                break;

            default:
                throw new QueryBuilderError(\sprintf("Unsupport merge conflict mode: %s", (string) $mode));
        }

        // WHEN NOT MATCHED THEN
        $output[] = 'when not matched then insert into ' . $escapedInsertTable;
        $output[] = '(' . $this->doFormatColumnNameList($context, $columns) . ')';
        $output[] = 'values (' . \implode(', ', $usingColumnMap) . ')';

        // RETURNING
        $return = $query->getAllReturn();
        if ($return) {
            $output[] = $this->doFormatReturning($context, $return, $this->doFormatOutputNewRowIdentifier($table));
        }

        return \implode("\n", $output);
    }

    /**
     * Format given insert query.
     */
    protected function formatInsert(Insert $query, WriterContext $context): string
    {
        $output = [];

        $columns = $query->getAllColumns();
        $table = $query->getTable();

        $output[] = $this->doFormatWith($context, $query->getAllWith());
        // From SQL 92 standard, INSERT queries don't have table alias
        $output[] = 'insert into ' . $this->escaper->escapeIdentifier($table->getName());

        // Columns.
        if ($columns) {
            $output[] = '(' . $this->doFormatColumnNameList($context, $columns) . ')';
        }

        $using = $query->getQuery();
        if ($using instanceof ConstantTable) {
            if (\count($columns)) {
                $output[] = $this->doFormatConstantTable($using, $context, null, true);
            } else {
                // Assume there is no specific values, for PostgreSQL, we need to set
                // "DEFAULT VALUES" explicitely, for MySQL "() VALUES ()" will do the
                // trick
                $output[] = $this->doFormatInsertNoValuesStatement($context);
            }
        } else {
            $output[] = $this->format($using, $context);
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = $this->doFormatReturning($context, $return, $this->doFormatOutputNewRowIdentifier($table));
        }

        return \implode("\n", $output);
    }

    /**
     * Format given delete query.
     */
    protected function formatDelete(Delete $query, WriterContext $context): string
    {
        $output = [];

        $table = $query->getTable();

        $output[] = $this->doFormatWith($context, $query->getAllWith());
        // This is not SQL-92 compatible, we are using USING..JOIN clause to
        // do joins in the DELETE query, which is not accepted by the standard.
        $output[] = 'delete from ' . $this->format($table, $context, true);

        $transformFirstJoinAsFrom = true;

        $from = $query->getAllFrom();
        if ($from) {
            $transformFirstJoinAsFrom = false;
            $output[] = ', ';
            $output[] = $this->doFormatFrom($context, $from, 'using');
        }

        $join = $query->getAllJoin();
        if ($join) {
            $output[] = $this->doFormatJoin($context, $join, $transformFirstJoinAsFrom, 'using', $query);
        }

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = 'where ' . $this->format($where, $context, true);
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = $this->doFormatReturning($context, $return, $this->doFormatOutputNewRowIdentifier($table));
        }

        return \implode("\n", \array_filter($output));
    }

    /**
     * Format given update query.
     */
    protected function formatUpdate(Update $query, WriterContext $context): string
    {
        $output = [];

        $columns = $query->getUpdatedColumns();
        if (empty($columns)) {
            throw new QueryBuilderError("Cannot run an update query without any columns to update.");
        }

        $table = $query->getTable();

        //
        // Specific use case for DELETE, there might be JOIN, this valid for
        // all of PostgreSQL, MySQL and MSSQL.
        //
        // We have three variants to implement:
        //
        //  - PgSQL: UPDATE FROM a SET x = y FROM b, c JOIN d WHERE (SQL-92),
        //
        //  - MySQL: UPDATE FROM a, b, c, JOIN d SET x = y WHERE
        //
        //  - MSSQL: UPDATE SET x = y FROM a, b, c JOIN d WHERE
        //
        // Current implementation is SQL-92 standard (and PostgreSQL which
        // strictly respect the standard for most of its SQL syntax).
        //
        // Also note that MSSQL will allow UPDATE on a CTE query for example,
        // MySQL will allow UPDATE everywhere, in all cases that's serious
        // violations of the SQL standard and probably quite a dangerous thing
        // to use, so it's not officialy supported, even thought using some
        // expression magic you can write those queries.
        //

        $output[] = $this->doFormatWith($context, $query->getAllWith());
        $output[] = 'update ' . $this->format($table, $context);
        $output[] = 'set ' . $this->doFormatUpdateSet($context, $columns);

        $transformFirstJoinAsFrom = true;

        $from = $query->getAllFrom();
        if ($from) {
            $transformFirstJoinAsFrom = false;
            $output[] = $this->doFormatFrom($context, $from, 'from');
        }

        $join = $query->getAllJoin();
        if ($join) {
            $output[] = $this->doFormatJoin($context, $join, $transformFirstJoinAsFrom, 'from', $query);
        }

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = 'where ' . $this->format($where, $context, true);
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = $this->doFormatReturning($context, $return, $this->doFormatOutputNewRowIdentifier($table));
        }

        return \implode("\n", \array_filter($output));
    }

    /**
     * Format given select query.
     */
    protected function formatSelect(Select $query, WriterContext $context): string
    {
        $output = [];
        $output[] = $this->doFormatWith($context, $query->getAllWith());
        $output[] = "select";
        if ($query->isDistinct()) {
            $output[] = "distinct";
        }
        $output[] = $this->doFormatSelect($context, $query->getAllColumns());

        $from = $query->getAllFrom();
        if ($from) {
            $output[] = $this->doFormatFrom($context, $from, 'from');
        }

        $join = $query->getAllJoin();
        if ($join) {
            $output[] = $this->doFormatJoin($context, $join);
        }

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = 'where ' . $this->format($where, $context);
        }

        $output[] = $this->doFormatGroupBy($context, $query->getAllGroupBy());

        $having = $query->getHaving();
        if (!$having->isEmpty()) {
            $output[] = 'having ' . $this->format($having, $context);
        }

        if ($windows = $query->getAllWindows()) {
            $output[] = $this->doFormatWindows($context, $windows);
        }

        if ($order = $query->getAllOrderBy()) {
            $output[] = $this->doFormatOrderBy($context, $order);
        }
        list($limit, $offset) = $query->getRange();
        $output[] = $this->doFormatRange($context, $limit, $offset, (bool) $order);

        foreach ($query->getUnion() as $expression) {
            $output[] = "union " . $this->format($expression, $context);
        }

        if ($query->isForUpdate()) {
            $output[] = "for update";
        }

        return \implode("\n", \array_filter($output));
    }

    /**
     * Format raw expression.
     */
    protected function formatRaw(Raw $expression, WriterContext $context): string
    {
        return $this->parseExpression($expression, $context);
    }

    /**
     * Format raw query.
     */
    protected function formatRawQuery(RawQuery $query, WriterContext $context)
    {
        return $this->parseExpression($query, $context);
    }

    /**
     * Format column all expression.
     */
    protected function formatColumnAll(ColumnAll $expression, WriterContext $context): string
    {
        if ($namespace = $expression->getNamespace()) {
            return $this->escaper->escapeIdentifier($namespace) . '.*';
        }
        return '*';
    }

    /**
     * Format table name expression.
     */
    protected function formatTableName(TableName $expression, WriterContext $context): string
    {
        return $this->formatIdentifier($expression, $context);
    }

    /**
     * Format identifier expression.
     */
    protected function formatIdentifier(Identifier $expression, WriterContext $context): string
    {
        // Allow selection such as "table".*
        $target = $expression->getName();

        if (!$expression instanceof ColumnName || '*' !== $target) {
            $target = $this->escaper->escapeIdentifier($target);
        }

        if ($namespace = $expression->getNamespace()) {
            return $this->escaper->escapeIdentifier($namespace) . '.' . $target;
        }
        return $target;
    }

    /**
     * Format value expression.
     */
    protected function formatValue(Value $expression, WriterContext $context): string
    {
        $index = $context->append($expression->getValue(), $expression->getType());

        // @todo For now this is hardcoded, but later this will be more generic
        //   fact is that for deambiguation, PostgreSQL needs arrays to be cast
        //   explicitly, otherwise it'll interpret it as a string; This might
        //   the case for some other types as well.
        $ret = $this->escaper->writePlaceholder($index);

        if ($type = $expression->getCastToType()) {
            return $this->doFormatCastExpression($ret, $type, $context);
        }

        return $ret;
    }

    /**
     * Format array expression.
     */
    protected function formatArrayValue(ArrayValue $value, WriterContext $context): string
    {
        $inner = '';
        foreach ($value->getValues() as $item) {
            if ($inner) {
                $inner .= ', ';
            }
            $inner .= $this->format($item, $context, true);
        }

        $output = 'array[' . $inner .  ']';

        if ($value->shouldCast() && ($valueType = $value->getValueType())) {
            return $this->doFormatCastExpression($output, $valueType->toArray(), $context);
        }
        return $output;
    }

    /**
     * Format null expression.
     */
    protected function formatNullValue(NullValue $expression, WriterContext $context): string
    {
        return 'null';
    }

    /**
     * Format cast expression.
     */
    protected function doFormatCastExpression(string $expressionString, string|Type $type, WriterContext $context): string
    {
        $type = Type::create($type);
        $typeString = $this->typeConverter->getSqlTypeName($type);

        if ($type->array) {
            $typeString .= '[]';
        }

        return 'cast(' . $expressionString . ' as ' . $typeString . ')';
    }

    /**
     * Format cast expression.
     */
    protected function formatCast(Cast $value, WriterContext $context): string
    {
        $expression = $value->getExpression();
        $expressionString = $this->format($expression, $context, true);

        // In this specific case, ROW() must contain the ROW keyword
        // otherwise it creates ambiguities.
        if ($expression instanceof Row) {
            $expressionString = 'row' . $expressionString;
        }

        return $this->doFormatCastExpression($expressionString, $value->getCastToType(), $context);
    }

    /**
     * Format LIKE pattern expression.
     */
    protected function formatLikePattern(LikePattern $expression, WriterContext $context): string
    {
        $escapedValue = null;
        if ($expression->hasValue()) {
            $escapedValue = $this->escaper->escapeLike($expression->getUnsafeValue());
        }

        $pattern = $expression->getPattern($escapedValue);

        return $this->escaper->escapeLiteral($pattern);
    }

    /**
     * Format SIMILAR TO pattern expression.
     */
    protected function formatSimilarToPattern(SimilarToPattern $expression, WriterContext $context): string
    {
        $escapedValue = null;
        if ($expression->hasValue()) {
            $escapedValue = $this->escaper->escapeSimilarTo($expression->getUnsafeValue());
        }

        $pattern = $expression->getPattern($escapedValue);

        return $this->escaper->escapeLiteral($pattern);
    }

    /**
     * Does the target dialect allows aggregate function name escaping.
     */
    protected function shouldEscapeAggregateFunctionName(): bool
    {
        return true;
    }

    /**
     * Use the CASE WHEN THEN END trick for simulating FILTER.
     *
     * TL;DR; Any statement such as:
     *    aggregate(expression) FILTER (WHERE condition)
     * Can be replaced by:
     *    aggregate(CASE WHEN condition THEN expression END)
     *
     * With the only exception of COUNT(*), then:
     *    COUNT(*) FILTER (WHERE condition)
     * Becomes:
     *    COUNT(CASE WHEN condition THEN 1 END)
     *
     * @see https://modern-sql.com/feature/filter
     */
    protected function doFormatAggregateWithoutFilter(Aggregate $expression, WriterContext $context): string
    {
        if ($this->shouldEscapeAggregateFunctionName()) {
            $output = $this->escaper->escapeIdentifier($expression->getFunctionName());
        } else {
            $output = $expression->getFunctionName();
        }

        $column = $expression->getColumn();
        $filter = $expression->getFilter();

        if ($filter && !$filter->isEmpty()) {
            $output .= '(case when '
                . $this->format($filter, $context)
                . ' then '
                . $this->format($column, $context)
                . ' end)'
            ;
        } else if ($column) {
            $output .= '(' . $this->format($column, $context) . ')';
        } else {
            $output .= '()';
        }

        if ($over = $expression->getOverWindow()) {
            $output .= ' over ' . $this->format($over, $context, !$over instanceof Window);
        }

        return $output;
    }

    /**
     * Format aggregation function in SELECT AGGR(...) FILTER (...) OVER (...).
     */
    protected function formatAggregate(Aggregate $expression, WriterContext $context): string
    {
        if ($this->shouldEscapeAggregateFunctionName()) {
            $output = $this->escaper->escapeIdentifier($expression->getFunctionName()) . '(';
        } else {
            $output = $expression->getFunctionName() . '(';
        }

        if ($column = $expression->getColumn()) {
            $output .= $this->format($column, $context);
        }

        $output .= ')';

        if ($filter = $expression->getFilter()) {
            $output .= ' filter (where ' . $this->format($filter, $context, false) . ')';
        }

        if ($over = $expression->getOverWindow()) {
            if ($over instanceof Window) {
                $output .= ' over ' . $this->format($over, $context, true);
            } else {
                $output .= ' over (' . $this->format($over, $context, false) . ')';
            }
        }

        return $output;
    }

    /**
     * Format an expression with an alias.
     */
    protected function formatAliased(Aliased $expression, WriterContext $context): string
    {
        $alias = $expression->getAlias();
        $nestedExpression = $expression->getExpression();

        if ($alias) {
            // Exception for constant table, see doFormatConstantTable().
            if ($nestedExpression instanceof ConstantTable) {
                return $this->doFormatConstantTable($nestedExpression, $context, $alias, false);
            }

            return $this->format($nestedExpression, $context, true) . ' as ' . $this->escaper->escapeIdentifier($alias);
        }

        // Do not enforce parenthesis, parent will do it for us.
        return $this->format($nestedExpression, $context, false);
    }

    /**
     * Uses the connection driven escape sequences to build the parameter
     * matching regex.
     */
    private function buildParameterRegex(): void
    {
        /*
         * Escape sequence matching magical regex.
         *
         * Order is important:
         *
         *   - ESCAPE will match all driver-specific string escape sequence,
         *     therefore will prevent any other matches from happening inside,
         *
         *   - "??" will always superseed "?*",
         *
         *   - "?::WORD" will superseed "?",
         *
         *   - any "::WORD" sequence, which is a valid SQL cast, will be left
         *     as-is and required no rewrite.
         *
         * After some thoughts, this needs serious optimisation.
         *
         * I believe that a real parser would be much more efficient, if it was
         * written in any language other than PHP, but right now, preg will
         * actually be a lot faster than we will ever be.
         *
         * This regex is huge, but contain no backward lookup, does not imply
         * any recursivity, it should be fast enough.
         */
        $parameterMatch = '@
            ESCAPE
            (\?\?)|                     # Matches ??
            (\?((\:\:([\w]+(\[\]|)))|)) # Matches ?, ?::WORD, ?::WORD[] (placeholders)
            @x';

        // Please see this really excellent Stack Overflow answer:
        //   https://stackoverflow.com/a/23589204
        $patterns = [];

        foreach ($this->escaper->getEscapeSequences() as $sequence) {
            $sequence = \preg_quote($sequence);
            $patterns[] = \sprintf("%s.+?%s", $sequence, $sequence);
        }

        if ($patterns) {
            $this->matchParametersRegex = \str_replace('ESCAPE', \sprintf("(%s)|", \implode("|", $patterns)), $parameterMatch);
        } else {
            // @todo Not sure about this one, added ", ''" to please phpstan.
            $this->matchParametersRegex = \str_replace('ESCAPE', '', $parameterMatch);
        }
    }
}
