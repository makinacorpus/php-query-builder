<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\Between;
use MakinaCorpus\QueryBuilder\Expression\Comparison;
use MakinaCorpus\QueryBuilder\Expression\Like;
use MakinaCorpus\QueryBuilder\Expression\LikePattern;
use MakinaCorpus\QueryBuilder\Expression\Row;
use MakinaCorpus\QueryBuilder\Expression\SimilarTo;
use MakinaCorpus\QueryBuilder\Expression\SimilarToPattern;

/**
 * Where expressions factory methods.
 *
 * This is excluded from where for pure code readability purpose.
 *
 * The Where class is reponsible for the specification pattern implementation
 * and implicit value to expression conversion, whereas this class implements
 * the factory pattern for dedicated comparison expressions.
 *
 * But basically, they belong to the same place.
 */
trait WhereBuilder
{
    /*
    const ARRAY_CONTAIN = '@>';
    const ARRAY_CONTAINED_BY = '<@';
    const ARRAY_EQUAL = '=';
    const ARRAY_GREATER = '>';
    const ARRAY_GREATER_OR_EQUAL = '>=';
    const ARRAY_LESS = '<';
    const ARRAY_LESS_OR_EQUAL = '<=';
    const ARRAY_NOT_EQUAL = '<>';
    const ARRAY_OVERLAP = '&&';
    /* Those are NOT comparison operators. * /
    const ARRAY_CONCAT = '||'; // Most interesting one.

    const JSONB_CONTAIN = '@>'; // JSON <@ JSON
    const JSONB_CONTAIN_KEY = '?'; // JSON ? text
    const JSONB_CONTAIN_KEY_ALL = '?&'; // JSON ?& text[], eg. array['a', 'b']
    const JSONB_CONTAIN_KEY_ANY = '?|'; // JSON ?| text[], eg. array['a', 'b']
    const JSONB_CONTAINED_BY = '<@'; // JSON @> JSON
    /* Those are NOT comparison operators. * /
    const JSON_ARRAY_ELEMENT_AT = '->>'; // JSON ->> int
    const JSON_OBJECT_ELEMENT_AT = '->'; // JSON -> int|text, negative int count from the end.
    const JSON_PATH = '#>'; // JSON #> PATH, eg. '{a, b}'
    const JSON_PATH_AS_TEXT = '#>>'; // JSON #>> PATH, eg. '{a, 2}'
    const JSONB_CONCAT = '||'; // JSON || JSON
    const JSONB_DELETE = '-'; // JSON - int|text, negative int count from the end.
    const JSONB_DELETE_PATH = '#-'; // JSON #- PATH, eg. '{a, b}'
     */

    /**
     * Allows using this trait on other components that the Where class.
     */
    abstract protected function getInstance(): Where;

    /**
     * Add a comparison condition.
     *
     * @param mixed $column
     *   Column is the left operand, if a string is given, assume a column name.
     * @param mixed $value
     *   Value is the right operand, if not an expression, assume a user given
     *   PHP value that will be send to arguments.
     */
    public function compare(mixed $column = null, mixed $value = null, string $operator = Comparison::EQUAL): static
    {
        // @todo Side effect from some sugar candy functions, we sometime
        //   get a Where instance here. We should not care about the
        //   default operator then.
        if (null === $value && (\is_callable($column) || $column instanceof Where)) {
            return $this->raw($column);
        }

        $column = ExpressionHelper::column($column);

        if (!$this->operatorNeedsValue($operator)) {
            if (null !== $value) {
                throw new QueryBuilderError(\sprintf("Operator %s cannot not have a right operand.", $operator));
            }

            $this->getInstance()->with(new Comparison($column, null, $operator));

            return $this;

        }

        if (\is_array($value)) {
            foreach ($value as $index => $current) {
                $value[$index] = ExpressionHelper::value($current);
            }
        }

        $value = ExpressionHelper::value($value, true);

        if (Comparison::EQUAL === $operator || Comparison::NOT_EQUAL === $operator) {
            // Shortcut allowing the user to pass an array of value for
            // "in" and "not in" operators.
            if ($value instanceof TableExpression || $value instanceof Row) {
                $this->getInstance()->with(
                    new Comparison(
                        $column,
                        $value,
                        Comparison::EQUAL === $operator ? Comparison::IN : Comparison::NOT_IN
                    )
                );
            } else {
                $this->getInstance()->with(new Comparison($column, $value, $operator));
            }

            return $this;
        }

        $this->getInstance()->with(new Comparison($column, $value, $operator));

        return $this;
    }

    /**
     * Add an abitrary SQL expression.
     *
     * @param callable|string|Expression $expression
     *   SQL string, which may contain parameters.
     * @param mixed $arguments
     *   Parameters for the arbitrary SQL.
     */
    public function raw(callable|string|Expression $expression, mixed $arguments = []): static
    {
        $expression = ExpressionHelper::raw($expression, $arguments, $this);

        // User may have given an arrow function as callable that returned self.
        if (null !== $expression && $expression !== $this) {
            $this->getInstance()->with($expression);
        }

        return $this;
    }

    /**
     * Negate another expression.
     */
    public function notRaw(callable|string|Expression $expression, mixed $arguments = []): static
    {
        $this->getInstance()->notWith(ExpressionHelper::raw($expression, $arguments));

        return $this;
    }

    /**
     * Add an exists condition.
     */
    public function exists(mixed $expression): static
    {
        $this->getInstance()->with(new Comparison(null, ExpressionHelper::raw($expression), Comparison::EXISTS));

        return $this;
    }

    /**
     * Add an exists condition.
     */
    public function notExists(mixed $expression): static
    {
        $this->getInstance()->with(new Comparison(null, ExpressionHelper::raw($expression), Comparison::NOT_EXISTS));

        return $this;
    }

    /**
     * '=' condition.
     *
     * If value is an array, this will be converted to a 'in' condition.
     */
    public function isEqual(callable|string|Expression $column, mixed $value): static
    {
        return $this->compare($column, $value, Comparison::EQUAL);
    }

    /**
     * '<>' condition.
     *
     * If value is an array, this will be converted to a 'not in' condition.
     */
    public function isNotEqual(callable|string|Expression $column, mixed $value): static
    {
        return $this->compare($column, $value, Comparison::NOT_EQUAL);
    }

    /**
     * 'like' condition.
     */
    public function isLike(callable|string|Expression $column, string|Expression $pattern, ?string $value = null, ?string $wildcard = null): static
    {
        $pattern = \is_string($pattern) ? new LikePattern($pattern, $value, $wildcard) : $pattern;

        $this->getInstance()->with(new Like($column, $pattern, true));

        return $this;
    }

    /**
     * 'not like' condition.
     *
     * @param callable|string|Expression $column
     *   Column, or expression that can be compared against, anything will do.
     * @param string $pattern
     *   Any string with % and _ inside, and  for value, use ? for value replacement.
     * @param ?string $value
     *   Any value to replace within pattern.
     * @param ?string $wildcard
     *   Wilcard if different, default is '?'.
     */
    public function isNotLike(callable|string|Expression $column, string|Expression $pattern, ?string $value = null, ?string $wildcard = null): static
    {
        $pattern = \is_string($pattern) ? new LikePattern($pattern, $value, $wildcard) : $pattern;

        $this->getInstance()->notWith(new Like($column, $pattern, true));

        return $this;
    }

    /**
     * 'ilike' condition.
     *
     * @param callable|string|Expression $column
     *   Column, or expression that can be compared against, anything will do.
     * @param string $pattern
     *   Any string with % and _ inside, and  for value, use ? for value replacement.
     * @param ?string $value
     *   Any value to replace within pattern.
     * @param ?string $wildcard
     *   Wilcard if different, default is '?'.
     */
    public function isLikeInsensitive(callable|string|Expression $column, string|Expression $pattern, ?string $value = null, ?string $wildcard = null): static
    {
        $pattern = \is_string($pattern) ? new LikePattern($pattern, $value, $wildcard) : $pattern;

        $this->getInstance()->with(new Like($column, $pattern, false));

        return $this;
    }

    /**
     * 'not ilike' condition.
     */
    public function isNotLikeInsensitive(callable|string|Expression $column, string|Expression $pattern, ?string $value = null, ?string $wildcard = null): static
    {
        $pattern = \is_string($pattern) ? new LikePattern($pattern, $value, $wildcard) : $pattern;

        $this->getInstance()->notWith(new Like($column, $pattern, false));

        return $this;
    }

    /**
     * 'similar to' condition, same as 'like' but with regex.
     *
     * @param callable|string|Expression $column
     *   Column, or expression that can be compared against, anything will do.
     * @param string $pattern
     *   Any string with SQL standard reserved characters inside, and for value,
     *   use ? for value replacement.
     * @param ?string $value
     *   Any value to replace within pattern.
     * @param ?string $wildcard
     *   Wilcard if different, default is '?'.
     */
    public function isSimilarTo(callable|string|Expression $column, string|Expression $pattern, ?string $value = null, ?string $wildcard = null): static
    {
        $pattern = \is_string($pattern) ? new SimilarToPattern($pattern, $value, $wildcard) : $pattern;

        $this->getInstance()->with(new SimilarTo($column, $pattern));

        return $this;
    }

    /**
     * 'not similar to' condition, same as 'not like' but with regex.
     */
    public function isNotSimilarTo(callable|string|Expression $column, string|Expression $pattern, ?string $value = null, ?string $wildcard = null): static
    {
        $pattern = \is_string($pattern) ? new SimilarToPattern($pattern, $value, $wildcard) : $pattern;

        $this->getInstance()->notWith(new SimilarTo($column, $pattern));

        return $this;
    }

    /**
     * 'in' condition.
     */
    public function isIn(callable|string|Expression $column, mixed $values): static
    {
        return $this->compare($column, $values, Comparison::IN);
    }

    /**
     * 'not in' condition.
     */
    public function isNotIn(callable|string|Expression $column, mixed $values): static
    {
        return $this->compare($column, $values, Comparison::NOT_IN);
    }

    /**
     * '>' condition.
     */
    public function isGreater(callable|string|Expression $column, mixed $value): static
    {
        return $this->compare($column, $value, Comparison::GREATER);
    }

    /**
     * '<' condition.
     */
    public function isLess(callable|string|Expression $column, mixed $value): static
    {
        return $this->compare($column, $value, Comparison::LESS);
    }

    /**
     * '>=' condition.
     */
    public function isGreaterOrEqual(callable|string|Expression $column, mixed $value): static
    {
        return $this->compare($column, $value, Comparison::GREATER_OR_EQUAL);
    }

    /**
     * '<=' condition.
     */
    public function isLessOrEqual(callable|string|Expression $column, mixed $value): static
    {
        return $this->compare($column, $value, Comparison::LESS_OR_EQUAL);
    }

    /**
     * 'between' condition.
     */
    public function isBetween(callable|string|Expression $column, mixed $from, mixed $to): static
    {
        $this->getInstance()->with(
            new Between(
                ExpressionHelper::column($column),
                ExpressionHelper::value($from),
                ExpressionHelper::value($to)
            )
        );

        return $this;
    }

    /**
     * 'not between' condition.
     */
    public function isNotBetween(callable|string|Expression $column, mixed $from, mixed $to): static
    {
        $this->getInstance()->notWith(
            new Between(
                ExpressionHelper::column($column),
                ExpressionHelper::value($from),
                ExpressionHelper::value($to)
            )
        );

        return $this;
    }

    /**
     * Add an is null condition.
     */
    public function isNull(callable|string|Expression $column): static
    {
        return $this->compare($column, null, Comparison::IS_NULL);
    }

    /**
     * Add an is not null condition.
     */
    public function isNotNull(callable|string|Expression $column): static
    {
        return $this->compare($column, null, Comparison::NOT_IS_NULL);
    }

    /**
     * Array contains.
     *
     * @param int|float|string|array|Expression $value
     *
    public function arrayContain(callable|string|array|Expression $column, mixed $value): static
    {
        return $this->compare(
            $this->normalizeArrayColumn($column),
            $this->normalizeArray($value),
            self::ARRAY_CONTAIN
        );
    }
     */

    /**
     * Array is contained by.
     *
     * Warning: this is likely to work only with PostgreSQL.
     *
     * @param int|float|string|array|Expression $value
     *
    public function arrayContainedBy(callable|string|array|Expression $column, mixed $value): static
    {
        return $this->compare(
            $this->normalizeArrayColumn($column),
            $this->normalizeArray($value),
            self::ARRAY_CONTAINED_BY
        );
    }
     */

    /**
     * Array is equal to.
     *
     * Warning: this is likely to work only with PostgreSQL.
     *
     * @param int|float|string|array|Expression $value
     *
    public function arrayEqual(callable|string|array|Expression $column, mixed $value): static
    {
        return $this->compare(
            $this->normalizeArrayColumn($column),
            $this->normalizeArray($value),
            self::ARRAY_EQUAL
        );
    }
     */

    /**
     * Array is greated than.
     *
     * Warning: this is likely to work only with PostgreSQL.
     *
     * @param int|float|string|array|Expression $value
     *
    public function arrayGreater(callable|string|array|Expression $column, mixed $value): static
    {
        return $this->compare(
            $this->normalizeArrayColumn($column),
            $this->normalizeArray($value),
            self::ARRAY_GREATER
        );
    }
     */

    /**
     * Array is greater or equal than.
     *
     * Warning: this is likely to work only with PostgreSQL.
     *
     * @param int|float|string|array|Expression $value
     *
    public function arrayGreaterOrEqual(callable|string|array|Expression $column, mixed $value): static
    {
        return $this->compare(
            $this->normalizeArrayColumn($column),
            $this->normalizeArray($value),
            self::ARRAY_GREATER_OR_EQUAL
        );
    }
     */

    /**
     * Array is less than.
     *
     * Warning: this is likely to work only with PostgreSQL.
     *
     * @param int|float|string|array|Expression $value
     *
    public function arrayLess(callable|string|array|Expression $column, mixed $value): static
    {
        return $this->compare(
            $this->normalizeArrayColumn($column),
            $this->normalizeArray($value),
            self::ARRAY_LESS
        );
    }
     */

    /**
     * Array is less or equal than.
     *
     * Warning: this is likely to work only with PostgreSQL.
     *
     * @param int|float|string|array|Expression $value
     *
    public function arrayLessOrEqual(callable|string|array|Expression $column, mixed $value): static
    {
        return $this->compare(
            $this->normalizeArrayColumn($column),
            $this->normalizeArray($value),
            self::ARRAY_LESS_OR_EQUAL
        );
    }
     */

    /**
     * Arrays are not equal.
     *
     * Warning: this is likely to work only with PostgreSQL.
     *
     * @param int|float|string|array|Expression $value
     *
    public function arrayNotEqual(callable|string|array|Expression $column, mixed $value): static
    {
        return $this->compare(
            $this->normalizeArrayColumn($column),
            $this->normalizeArray($value),
            self::ARRAY_NOT_EQUAL
        );
    }
     */

    /**
     * Arrays have common entries.
     *
     * Warning: this is likely to work only with PostgreSQL.
     *
     * @param int|float|string|array|Expression $value
     *
    public function arrayOverlap(callable|string|array|Expression $column, mixed $value): static
    {
        return $this->compare(
            $this->normalizeArrayColumn($column),
            $this->normalizeArray($value),
            self::ARRAY_OVERLAP
        );
    }
     */

    /**
     * JSON contain key.
     *
     * Warning: this is likely to work only with PostgreSQL.
     *
    public function jsonContainKey(callable|string|array|Expression $column, string $value): static
    {
        return $this->compare(
            $this->normalizeJsonColumn($column),
            $value,
            self::JSONB_CONTAIN_KEY
        );
    }
     */

    /**
     * JSON contain any key.
     *
     * Warning: this is likely to work only with PostgreSQL.
     *
     * @param int|float|string|array|Expression $value
     *
    public function jsonContainAnyKey(callable|string|array|Expression $column, mixed $value): static
    {
        return $this->compare(
            $this->normalizeJsonColumn($column),
            $this->normalizeJsonKeyArray($value),
            self::JSONB_CONTAIN_KEY_ANY
        );
    }
     */

    /**
     * JSON contain any key.
     *
     * Warning: this is likely to work only with PostgreSQL.
     *
     * @param int|float|string|array|Expression $value
     *
    public function jsonContainAllKey(callable|string|array|Expression $column, mixed $value): static
    {
        return $this->compare(
            $this->normalizeJsonColumn($column),
            $this->normalizeJsonKeyArray($value),
            self::JSONB_CONTAIN_KEY_ALL
        );
    }
     */

    /**
     * JSON contain other JSON expression.
     *
     * Warning: this is likely to work only with PostgreSQL.
     *
     * @param int|float|string|array|Expression $value
     *
    public function jsonContainJson(callable|string|array|Expression $column, mixed $value): static
    {
        return $this->compare(
            $this->normalizeJsonColumn($column),
            $this->normalizeJson($value),
            self::JSONB_CONTAIN
        );
    }
     */

    /**
     * JSON is contained by other JSON expression.
     *
     * Warning: this is likely to work only with PostgreSQL.
     *
     * @param int|float|string|array|Expression $value
     *
    public function jsonContainedByJson(callable|string|array|Expression $column, mixed $value): static
    {
        return $this->compare(
            $this->normalizeJsonColumn($column),
            $this->normalizeJson($value),
            self::JSONB_CONTAINED_BY
        );
    }
     **/

    /**
     * Does operator needs right hand side.
     */
    private function operatorNeedsValue(string $operator): bool
    {
        return $operator !== Comparison::IS_NULL && $operator !== Comparison::NOT_IS_NULL;
    }
}
