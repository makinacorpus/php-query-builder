<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\ArrayValue;
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\DateInterval;
use MakinaCorpus\QueryBuilder\Expression\DateIntervalUnit;
use MakinaCorpus\QueryBuilder\Expression\NullValue;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Row;
use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Input normalization functions.
 *
 * This acts a factory, each method depends upon the desired behavior regarding
 * user input: for example, when an argument is explicitely typed "column", you
 * might want to normalize strings as being column names.
 *
 * Almost all methods will, in order:
 *   - execute user callback as input, and use the return as an expression,
 *   - attempt non Expression values conversion as an Expression which fit
 *     the use case,
 *   - raise an exception when user given Expression::returns() returns false,
 *   - allow any user given Expression instance to pass without validation.
 * Exceptions are:
 *   - methods named strict*() will check return type for being stricted than
 *     simply an expression.
 *
 * @internal
 *   This is meant to be used internally and is subject to breaking changes.
 */
final class ExpressionHelper
{
    /**
     * Normalize arguments.
     */
    public static function arguments(mixed $arguments): array
    {
        if (null === $arguments) {
            return [];
        }
        if (\is_array($arguments)) {
            return \array_values($arguments);
        }
        return [$arguments];
    }

    /**
     * Normalize array.
     */
    public static function json(mixed $value): Expression
    {
        $value = self::callable($value);

        if (null === $value) {
            return new NullValue();
        }

        if (empty($value)) {
            return new Value([], 'jsonb');
        }

        if ($value instanceof Expression) {
            return $value;
        }

        // We always cast as JSONB otherwise PostgreSQL will not
        // allow any search or comparison.
        // This delegate responsibility to convert the given value to
        // JSON to the driver.
        return new Value($value, 'jsonb');
    }

    /**
     * Normalize column, adds special treatment for JSON values.
     *
    private function normalizeJsonColumn(mixed $value): Expression
    {
        if ($value instanceof Expression) {
            return $value;
        }
        if (\is_array($value)) {
            // We always cast as JSONB otherwise PostgreSQL will not
            // allow any search or comparison.
            return new Value($value, 'jsonb');
        }
        return ExpressionFactory::column($value);
    }
     */

    /**
     * Normalize a json key array.
     *
    private function normalizeJsonKeyArray(mixed $value): Expression
    {
        if (\is_string($value)) {
            return new ArrayExpression([$value]);
        }
        if (\is_array($value)) {
            return new ArrayExpression($value);
        }
        throw new QueryBuilderError("JSON(b) key can only be a string or an array of string.");
    }
     */

    /**
     * Normalize column, adds special treatment for array values.
     *
    private function normalizeArrayColumn(mixed $value): Expression
    {
        if ($value instanceof Expression) {
            return $value;
        }
        if (\is_array($value)) {
            return new ArrayExpression($value);
        }
        return ExpressionFactory::column($value);
    }
     */

    public static function where(mixed $expression): Where
    {
        $expression = self::callable($expression);

        if (empty($expression)) {
            return new Where();
        }

        if ($expression instanceof Where) {
            return $expression;
        }

        return (new Where())->raw($expression);
    }

    /**
     * Normalize array.
     */
    public static function array(mixed $value): Expression
    {
        $value = self::callable($value);

        if (null === $value) {
            return new NullValue();
        }

        if (empty($value)) {
            return new ArrayValue([]);
        }

        if ($value instanceof Expression) {
            return $value;
        }

        // This delegate responsibility to convert the given value to
        // ARRAY to the driver.
        return new ArrayValue($value);
    }

    /**
     * Process callback, it might return anything.
     *
     * If null is returned, it considers that the callback took control.
     */
    public static function callable(mixed $expression, mixed $context = null): mixed
    {
        // Do not permit 'function_name' type callables, because values
        // are sometime strings which actually might be PHP valid function
        // names such as 'exp' for example.
        return (!\is_string($expression) && \is_callable($expression)) ? $expression($context) : $expression;
    }

    /**
     * Normalize input to be a column identifier or an expression that returns
     * a value that can be used as a column.
     */
    public static function column(mixed $expression, mixed $context = null): Expression
    {
        $expression = self::callable($expression);

        if (null === $expression || '' == $expression) {
            throw new QueryBuilderError("Expression cannot be null or empty");
        }

        if ($expression instanceof Expression) {
            if (!$expression->returns()) {
                throw new QueryBuilderError("Expression must return a value");
            }
            return $expression;
        }

        if (\is_string($expression)) {
            return new ColumnName($expression);
        }

        throw new QueryBuilderError(\sprintf(
            "Column reference must be a string or an instance of %s",
            Expression::class,
        ));
    }

    /**
     * Normalize input to be anything table-like such as table identifier,
     * select queries, constant tables.
     */
    public static function table(mixed $expression): Expression
    {
        if (null === $expression || '' == $expression) {
            throw new QueryBuilderError("Expression cannot be null or empty");
        }

        $expression = self::callable($expression);

        if ($expression instanceof Expression) {
            if (!$expression->returns()) {
                throw new QueryBuilderError("Expression must return a value");
            }
            return $expression;
        }

        if (\is_string($expression)) {
            return new TableName($expression);
        }

        throw new QueryBuilderError(\sprintf(
            "Table reference must be a string or an instance of %s",
            Expression::class,
        ));
    }

    /**
     * Normalize input to be a table identifier.
     */
    public static function tableName(mixed $expression): TableName
    {
        if (null === $expression || '' == $expression) {
            throw new QueryBuilderError("Expression cannot be null or empty");
        }

        $expression = self::callable($expression);

        if (\is_string($expression)) {
            return new TableName($expression);
        }

        if ($expression instanceof TableName) {
            return $expression;
        }

        throw new QueryBuilderError(\sprintf(
            "Table expression must be a %s",
            TableExpression::class,
        ));
    }

    /**
     * Normalize input to be a value.
     */
    public static function interval(mixed $expression): Expression
    {
        $expression = self::callable($expression);

        if (null === $expression) {
            return new NullValue();
        }

        if ($expression instanceof DateIntervalUnit) {
            return new DateInterval($expression);
        }

        if ($expression instanceof Expression) {
            if (!$expression->returns()) {
                throw new QueryBuilderError("Expression must return a value");
            }
            return $expression;
        }

        if ($expression instanceof \DateInterval || \is_array($expression) || \is_string($expression)) {
            return new DateInterval($expression);
        }

        throw new QueryBuilderError("Interval expression must be a \\DateInterval instance or an array<string,int>");
    }

    /**
     * Normalize input to be a value.
     */
    public static function date(mixed $expression): Expression
    {
        $expression = self::callable($expression);

        if (null === $expression) {
            return new NullValue();
        }

        if ($expression instanceof Expression) {
            if (!$expression->returns()) {
                throw new QueryBuilderError("Expression must return a value");
            }
            return $expression;
        }

        if ($expression instanceof \DateTimeInterface) {
            return new Value($expression, Type::timestamp(true));
        }

        if (\is_string($expression)) {
            try {
                return new Value(new \DateTimeImmutable($expression), Type::timestamp(true));
            } catch (\Throwable) {}
        }

        throw new QueryBuilderError("Interval expression must be a \\DateTimeInterface instance or parsable date string");
    }

    /**
     * Normalize input to be a value.
     */
    public static function integer(mixed $expression): Expression
    {
        $expression = self::callable($expression);

        if (null === $expression) {
            return new NullValue();
        }

        if ($expression instanceof Expression) {
            if (!$expression->returns()) {
                throw new QueryBuilderError("Expression must return a value");
            }
            return $expression;
        }

        if (!\is_numeric($expression)) {
            throw new QueryBuilderError("Value must be an int or castable as int");
        }

        return new Value((int) $expression, 'bigint');
    }

    /**
     * Normalize input to be a value.
     */
    public static function value(mixed $expression, bool $arrayAsRow = false): Expression
    {
        $expression = self::callable($expression);

        if (null === $expression) {
            return new NullValue();
        }

        if ($expression instanceof Expression) {
            if (!$expression->returns()) {
                throw new QueryBuilderError("Expression must return a value");
            }
            return $expression;
        }

        if ($arrayAsRow && \is_iterable($expression)) {
            return new Row($expression);
        }

        /*
         * @todo Array expression
         */

        return new Value($expression);
    }

    /**
     * Normalize input to be any expression, if a string is given consider
     * it's user-given raw SQL and create a Raw expression.
     *
     * If null is returned, this means that callback took control.
     */
    public static function raw(mixed $expression, mixed $arguments = null, mixed $context = null): ?Expression
    {
        if (null === $expression || '' == $expression) {
            throw new QueryBuilderError("Expression cannot be null or empty");
        }

        $expression = self::callable($expression, $context);

        // Callback did not return anything, but had the chance to play with context.
        // If $context === $expression, this means that the user used a short arrow
        // syntax that implicitely returned the value, we consider it as null.
        if ($context && null === $expression || $context === $expression) {
            return null;
        }

        if ($expression instanceof Expression) {
            if (!$expression->returns()) {
                throw new QueryBuilderError("Expression must return a value");
            }
            if ($arguments) {
                throw new QueryBuilderError(\sprintf(
                    "You cannot call pass \$arguments values if \$expression is an %s instance.",
                    Expression::class,
                ));
            }
            return $expression;
        }

        if (\is_scalar($expression)) {
            return new Raw($expression, $arguments);
        }

        throw new QueryBuilderError(\sprintf(
            "Raw expression must be a scalar or an instance of %s",
            Expression::class,
        ));
    }
}
