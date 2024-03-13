<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\TableExpression;
use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\Aggregate;
use MakinaCorpus\QueryBuilder\Expression\ColumnAll;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Window;
use MakinaCorpus\QueryBuilder\Query\Partial\FromClauseTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\HavingClauseTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\OrderByTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\SelectColumn;
use MakinaCorpus\QueryBuilder\Query\Partial\WhereClauseTrait;
use MakinaCorpus\QueryBuilder\Query\Where\WhereSelect;

/**
 * Represents a SELECT query.
 */
class Select extends AbstractQuery implements TableExpression
{
    use FromClauseTrait;
    use HavingClauseTrait;
    use OrderByTrait;
    use WhereClauseTrait;

    private bool $distinct = false;
    /** @var SelectColumn[] */
    private array $columns = [];
    private bool $forUpdate = false;
    private array $groups = [];
    private int $limit = 0;
    private int $offset = 0;
    private bool $performOnly = false;
    /** @var Window[] */
    private array $windows = [];
    /** @var Expression[] */
    private array $unions = [];
    private WhereSelect $where;
    private WhereSelect $having;

    /**
     * Build a new query.
     *
     * @param null|string|Expression $table
     *   SQL from table name or query that returns a constant or in memory
     *   table, if null, select from no table.
     * @param string $alias
     *   Alias for from clause table
     */
    public function __construct(mixed $table = null, ?string $alias = null)
    {
        if ($table) {
            $this->from($table, $alias);
        }
        $this->having = new WhereSelect($this);
        $this->where = new WhereSelect($this);
    }

    /**
     * Is this SELECT DISTINCT.
     */
    public function isDistinct(): bool
    {
        return $this->distinct;
    }

    /**
     * Mark SELECT DISTINCT ...
     */
    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }

    /**
     * Add another query to UNION with.
     */
    public function union(Expression $select): static
    {
        $this->unions[] = $select;

        return $this;
    }

    /**
     * Create a new SELECT query object to UNION with.
     */
    public function createUnion(mixed $table, ?string $tableAlias = null): Select
    {
        $select = new Select($table, $tableAlias);
        $this->union($select);

        return $select;
    }

    /**
     * Get UNION tables.
     *
     * @return Expression[]
     */
    public function getUnion(): array
    {
        return $this->unions;
    }

    /**
     * Set the query as a SELECT ... FOR UPDATE query.
     */
    public function forUpdate(): static
    {
        $this->forUpdate = true;

        return $this;
    }

    /**
     * Is this a SELECT ... FOR UPDATE.
     */
    public function isForUpdate(): bool
    {
        return $this->forUpdate;
    }

    /**
     * Explicitely tell to the driver we don't want any return.
     */
    public function performOnly(): static
    {
        $this->performOnly = true;

        return $this;
    }

    /**
     * Get select columns array.
     *
     * @return SelectColumn[]
     */
    public function getAllColumns(): array
    {
        return $this->columns;
    }

    /**
     * Remove everything from the current SELECT clause.
     */
    public function removeAllColumns(): static
    {
        $this->columns = [];

        return $this;
    }

    /**
     * Remove everything from the current ORDER clause.
     */
    public function removeAllOrder(): Select
    {
        $this->orders = [];

        return $this;
    }

    /**
     * Add a selected column.
     *
     * If you need to pass arguments, use a Expression instance or columnRaw().
     */
    public function column(mixed $expression, ?string $alias = null): static
    {
        if (null === $expression || '*' === $expression) {
            $column = new ColumnAll();
        } else {
            $column = ExpressionHelper::column($expression);
        }

        $this->columns[] = new SelectColumn($column, $alias);

        return $this;
    }

    /**
     * Add a selected column as a raw SQL expression.
     */
    public function columnRaw(mixed $expression, ?string $alias = null, mixed $arguments = null): static
    {
        if (null === $expression || '*' === $expression) {
            $column = new ColumnAll();
        } else {
            $column = ExpressionHelper::raw($expression, $arguments);
        }

        $this->columns[] = new SelectColumn($column, $alias);

        return $this;
    }

    /**
     * Add column with aggregation function.
     */
    public function columnAgg(string $function, mixed $expression, ?string $alias = null): static
    {
        $this->createColumnAgg($function, $expression, $alias);

        return $this;
    }

    /**
     * Create and  set a column with aggregation function.
     */
    public function createColumnAgg(string $function, mixed $expression, ?string $alias = null): Aggregate
    {
        if (null !== $expression) {
            $expression = ExpressionHelper::column($expression);
        }

        $ret = new Aggregate($function, $expression);

        $this->columns[] = new SelectColumn($ret, $alias);

        return $ret;
    }

    /**
     * Add COUNT(*|<column>) to SELECT clause.
     */
    public function count(mixed $column = null, ?string $alias = null, bool $distinct = false): static
    {
        if (null === $column || '*' === $column) {
            $column = new ColumnAll();
        } else {
            $column = ExpressionHelper::column($column);
        }

        if ($distinct) {
            $column = new Raw('distinct ?', [$column]);
        }

        $this->columnAgg('count', $column, $alias);

        return $this;
    }

    /**
     * Set or replace multiple columns at once.
     *
     * @param string[] $columns
     *   Keys are aliases, values are SQL statements; if you do not wish to
     *   set aliases, keep the numeric indexes, if you want to use an integer
     *   as alias, just write it as a string, for example: "42".
     */
    public function columns(array $columns): static
    {
        foreach ($columns as $alias => $statement) {
            if (\is_int($alias)) {
                $this->column($statement);
            } else {
                $this->column($statement, $alias);
            }
        }

        return $this;
    }

    /**
     * Find column index for given alias.
     */
    private function findColumnIndex(string $alias): ?int
    {
        foreach ($this->columns as $index => $data) {
            if ($data->getNameInSelect() === $alias) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Remove column from projection.
     */
    public function removeColumn(string $alias): static
    {
        $index = $this->findColumnIndex($alias);

        if (null !== $index) {
            unset($this->columns[$index]);
        }

        return $this;
    }

    /**
     * Does this project have the given column.
     */
    public function hasColumn(string $alias): bool
    {
        return null !== $this->findColumnIndex($alias);
    }

    /**
     * Get all WINDOW clauses.
     *
     * @return Window[]
     */
    public function getAllWindows(): array
    {
        return $this->windows;
    }

    /**
     * Create a window function, and register it at the FROM level.
     *
     * Eg.:
     *    SELECT
     *       foo() OVER (window_alias)
     *    FROM bar
     *    WINDOW window_alias AS (EXPR)
     */
    public function window(string $alias, mixed $partitionBy = null, mixed $orderBy = null, int $order = Query::ORDER_ASC, int $null = Query::NULL_IGNORE): static
    {
        $window = new Window(null, null, $alias);
        if ($partitionBy) {
            $window->partitionBy($partitionBy);
        }
        if ($orderBy) {
            $window->orderBy($orderBy, $order, $null);
        }

        $this->windows[] = $window;

        return $this;
    }

    /**
     * Create a window function, and register it at the FROM level, and
     * return the instance for building it.
     *
     * Eg.:
     *    SELECT
     *       foo() OVER (window_alias)
     *    FROM bar
     *    WINDOW window_alias AS (EXPR)
     */
    public function createWindow(string $alias): Window
    {
        return $this->windows[] = new Window(null, null, $alias);
    }

    /**
     * Get WHERE clause.
     */
    public function getWhere(): WhereSelect
    {
        return $this->where;
    }

    #[\Override]
    protected function getWhereInstance(): Where
    {
        return $this->where;
    }

    /**
     * Get HAVING clause.
     */
    public function getHaving(): WhereSelect
    {
        return $this->having;
    }

    #[\Override]
    protected function getHavingInstance(): Where
    {
        return $this->having;
    }

    /**
     * Get group by clauses array.
     */
    public function getAllGroupBy(): array
    {
        return $this->groups;
    }

    /**
     * Get query range.
     *
     * @return int[]
     *   First value is limit second is offset.
     */
    public function getRange(): array
    {
        return [$this->limit, $this->offset];
    }

    /**
     * Add a group by clause.
     *
     * @param callable|string|Expression $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement.
     */
    public function groupBy(callable|string|Expression $column): static
    {
        $this->groups[] = ExpressionHelper::column($column);

        return $this;
    }

    /**
     * Add a group by clause using raw SQL.
     *
     * @param callable|string|Expression $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement.
     */
    public function groupByRaw(callable|string|Expression $column): static
    {
        $this->groups[] = ExpressionHelper::raw($column);

        return $this;
    }

    /**
     * Set limit.
     */
    public function limit(int $limit = 0): static
    {
        if ($limit < 0) {
            throw new QueryBuilderError(\sprintf("limit must be a positive integer: %d given", $limit));
        }

        $this->limit = $limit;

        return $this;
    }

    /**
     * Set offset.
     */
    public function offset(int $offset = 0): static
    {
        if ($offset < 0) {
            throw new QueryBuilderError(\sprintf("offset must be a positive integer: %d given", $offset));
        }

        $this->offset = $offset;

        return $this;
    }

    /**
     * Set limit/offset.
     */
    public function range(int $limit = 0, int $offset = 0): static
    {
        $this->limit($limit);
        $this->offset($offset);

        return $this;
    }

    /**
     * Set limit/offset using a page number.
     */
    public function page(int $limit = 0, int $page = 1): static
    {
        if ($page < 1) {
            throw new QueryBuilderError(\sprintf("page must be a positive integer, starting with 1: %d given", $limit));
        }

        $this->range($limit, ($page - 1) * $limit);

        return $this;
    }

    /**
     * Get the count Select.
     *
     * @param string $countAlias
     *   Alias of the count column.
     *
     * @return Select
     *   Returned query will be a clone, the count row will be aliased with the
     *   given alias.
     */
    public function getCountQuery(string $countAlias = 'count'): Select
    {
        // @todo do not remove necessary fields for group by and other
        //   aggregates functions (SQL standard)
        return (clone $this)
            ->setOption('class', null) // @todo Remove?
            ->setOption('hydrator', null) // @todo Remove?
            ->removeAllColumns()
            ->removeAllOrder()
            ->range(0, 0)
            ->column(new Raw("count(*)"), $countAlias)
        ;
    }

    #[\Override]
    public function willReturnRows(): bool
    {
        return !$this->performOnly;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->cloneWith();
        $this->cloneFrom();
        $this->cloneOrder();
        $this->where = clone $this->where;
        $this->having = clone $this->having;
        foreach ($this->columns as $index => $column) {
            $this->columns[$index] = clone $column;
        }
    }
}
