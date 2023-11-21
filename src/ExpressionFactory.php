<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Expression\Aliased;
use MakinaCorpus\QueryBuilder\Expression\ArrayValue;
use MakinaCorpus\QueryBuilder\Expression\CaseWhen;
use MakinaCorpus\QueryBuilder\Expression\Cast;
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\Concat;
use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Expression\FunctionCall;
use MakinaCorpus\QueryBuilder\Expression\Identifier;
use MakinaCorpus\QueryBuilder\Expression\IfThen;
use MakinaCorpus\QueryBuilder\Expression\Modulo;
use MakinaCorpus\QueryBuilder\Expression\Not;
use MakinaCorpus\QueryBuilder\Expression\NullValue;
use MakinaCorpus\QueryBuilder\Expression\Random;
use MakinaCorpus\QueryBuilder\Expression\RandomInt;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Row;
use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\Expression\Value;

/**
 * Expressions factory methods.
 *
 * This is excluded from where for pure code readability purpose.
 *
 * Methods are static on this object, but when used over a Query object you
 * may call its method as instance methods, for example as such:
 *
 *    $expression = (new Update())->expression()->raw('some sql code');
 */
class ExpressionFactory
{
    /**
     * Create a `(expr) AS "alias"` expression.
     *
     * Parenthesis will be added depending upon the expression nature.
     */
    public static function aliased(mixed $expression, string $alias): Aliased
    {
        return new Aliased(ExpressionHelper::value($expression), $alias);
    }

    /**
     * Create an `ARRAY[val1, val2, ..]` value.
     *
     * @param bool $shouldCast
     *   If set to true, and if $valueType is not null, then the resulting array
     *   will be surrounded by a `CAST(expr AS valueType)` expression.
     */
    public static function array(mixed $values, ?string $valueType = null, bool $shouldCast = true): ArrayValue
    {
        if (!\is_iterable($values) && !\is_array($values)) {
            $values = [$values];
        }
        return new ArrayValue($values, $valueType, $shouldCast);
    }

    /**
     * Create a `CASE WHEN expr THEN expr ... ELSE expr END` expression.
     */
    public static function caseWhen(mixed $else = null): CaseWhen
    {
        return new CaseWhen($else);
    }

    /**
     * Create a CAST(expr AS type) expression.
     */
    public static function cast(mixed $expression, string $type): Cast
    {
        return new Cast($expression, $type);
    }

    /**
     * Create a properly escaped `"table"."column"` expression.
     *
     * @param bool $noAutomaticNamespace
     *   Set this to true if your column name contains any `.` and you don't
     *   have a table name.
     */
    public static function column(string $column, ?string $table = null, bool $noAutomaticNamespace = false): ColumnName
    {
        return new ColumnName($column, $table, $noAutomaticNamespace);
    }

    /**
     * Create an `expr || expr || expr` concatenation expression.
     *
     * Some dialects don't use the  `||` operator, this will be converted to
     * the `CONCAT(expr, expr, expr)` function call.
     */
    public static function concat(mixed ...$arguments): Concat
    {
        return new Concat(...$arguments);
    }

    /**
     * Create a `VALUES ((expr, expr), (expr, expr), ...)` expression.
     *
     * @param mixed[] $rows
     *   Rows to add to the table.
     * @param string[] $columns
     *   Depending upon where is written the constant table expression, you
     *   may add column name aliases, for exemple where writing SQL code such
     *   as SELECT FROM VALUES ((1, 2)) AS "table" ("col1", "col2").
     *   This allows you then to reference those aliase elswhere in the query.
     */
    public static function constantTable(mixed $rows = null, ?array $columns = null,): ConstantTable
    {
        return new ConstantTable($rows, $columns);
    }

    /**
     * Create `function(arg1, arg2, ...)` expression.
     */
    public static function functionCall(string $name, mixed ...$arguments): FunctionCall
    {
        return new FunctionCall($name, ...$arguments);
    }

    /**
     * Arbitrarily escape any string as an `"identifier"`.
     *
     * You can also use a namespace which will result in the following dot
     * notation:`"namespace"."identifier"`.
     *
     * Identifiers are table names, column names, table aliases,...
     * Anything that is not a value and not an SQL keyword can be escaped
     * as an identifier.
     */
    public static function identifier(string $name, ?string $namespace = null)
    {
        return new Identifier($name, $namespace);
    }

    /**
     * Create an if/then condition, which in all cases will be generated
     * as a `CASE WHEN condition THEN then ELSE else END`.
     */
    public static function ifThen(mixed $condition, mixed $then, mixed $else = null): IfThen
    {
        return new IfThen($condition, $then, $else);
    }

    /**
     * Negate any expression by prepending it with `NOT expr`.
     */
    public static function not(mixed $expression): Not
    {
        return new Not(ExpressionHelper::value($expression));
    }

    /**
     * Create a `NULL` expression.
     */
    public static function null(): NullValue
    {
        return new NullValue();
    }

    /**
     * Create a modulo arithmetic expression.
     */
    public static function mod(mixed $left, mixed $right): Modulo
    {
        return new Modulo(ExpressionHelper::value($left), ExpressionHelper::value($right));
    }

    /**
     * Create a `random()` expression.
     *
     * This generated an arbitrary float number between 0 and 1.
     *
     * This is not standard SQL, and some dialects might not support this.
     */
    public static function random(): Random
    {
        return new Random();
    }

    /**
     * Create a `FLOOR(random() * (max - min + 1) + min)` expression.
     *
     * This generates a random integer between min and max. 
     *
     * This is not standard SQL, and some dialects might not support this.
     */
    public static function randomInt(int $max, int $min = 0): RandomInt
    {
        return new RandomInt($max, $min);
    }

    /**
     * Allows you to inject raw SQL code.
     *
     * @param mixed $arguments
     *   If not an array, this will be converted to an array. Each value to
     *   escape must be replaced using `?`, each  `?` must have a corresponding
     *   value in the $arguments array.
     *   Arguments are positional so order must match. Arguments can be anything
     *   including expression instances as well.
     */
    public static function raw(string $expression, mixed $arguments = null): Raw
    {
        return new Raw($expression, $arguments);
    }

    /**
     * Creates a `ROW(val1, val2, ...)` expression.
     *
     * If you provide a composite type name, generated SQL will cast the
     * row using the given type name.
     *
     * When used in a constant table context, the `ROW` prefix will be omitted. 
     */
    public static function row(mixed $values, ?string $compositeTypeName = null): Row
    {
        return new Row($values, $compositeTypeName);
    }

    /**
     * Escape a table name.
     *
     * @param bool $noAutomaticNamespace
     *   Set this to true if your column name contains any `.` and you don't
     *   have a table name.
     */
    public static function table(string $name, ?string $alias = null, ?string $namespace = null, bool $noAutomaticNamespace = false): TableName
    {
        return new TableName($name, $alias, $namespace, $noAutomaticNamespace);
    }

    /**
     * Arbitrary value.
     */
    public static function value(mixed $value, ?string $type = null): Value
    {
        return new Value($value, $type);
    }

    /**
     * Create a new (expr AND expr AND ...) boolean clause.
     */
    public static function where(?string $operator = null): Where
    {
        return new Where($operator ?? Where::AND);
    }

    /**
     * Create a new (expr OR expr OR ...) boolean clause.
     */
    public static function whereOr(): Where
    {
        return self::where(Where::OR);
    }
}
