<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\TableName;
use MakinaCorpus\QueryBuilder\Query\Partial\FromClauseTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\ReturningQueryTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\WhereClauseTrait;
use MakinaCorpus\QueryBuilder\Query\Where\WhereUpdate;

/**
 * Represents an UPDATE query.
 */
class Update extends AbstractQuery
{
    use FromClauseTrait;
    use ReturningQueryTrait;
    use WhereClauseTrait;

    private TableName $table;
    /** @var Expression[] */
    private array $columns = [];
    private WhereUpdate $where;

    /**
     * Build a new query.
     *
     * @param string|Expression $table
     *   SQL FROM clause table name or expression.
     * @param string $alias
     *   Alias for FROM clause table.
     */
    public function __construct(string|Expression $table, ?string $alias = null)
    {
        $this->table = $this->normalizeStrictTable($table, $alias);
        $this->where = new WhereUpdate($this);
    }

    /**
     * Get FROM table.
     */
    public function getTable(): TableName
    {
        return $this->table;
    }

    /**
     * Set a column value to update.
     *
     * @param string $columnName
     *   Must be, as the SQL-92 standard states, a single column name without
     *   the table prefix or alias, it cannot be an expression.
     * @param mixed $expression
     *   The column value, if it's a string it can be a reference to any other
     *   field from the table or the FROM clause, as well as it can be raw
     *   SQL expression that returns only one row.
     *   Warning if a SelectQuery is passed here, it must return only one row
     *   else your database driver won't like it very much, and we cannot check
     *   this for you, since you could restrict the row count using WHERE
     *   conditions that matches the UPDATE table.
     */
    public function set(string $columnName, mixed $expression): static
    {
        if (false !== \strpos($columnName, '.')) {
            throw new QueryBuilderError("column names in the set part of an update query can only be a column name, without table prefix");
        }

        $this->columns[$columnName] = ExpressionHelper::value($expression);

        return $this;
    }

    /**
     * Set multiple column values to update.
     *
     * @param array<string,mixed|Expression> $values
     *   Keys are column names, as specified in the ::value() method, and values
     *   are statements as specified by the same method.
     */
    public function sets(array $values): static
    {
        foreach ($values as $column => $statement) {
            $this->set($column, $statement);
        }

        return $this;
    }

    /**
     * Get all updated columns.
     *
     * @return string[]|Expression[]
     *   Keys are column names, values are either strings or Expression instances.
     */
    public function getUpdatedColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get WHERE clause.
     */
    public function getWhere(): WhereUpdate
    {
        return $this->where;
    }

    #[\Override]
    protected function getWhereInstance(): Where
    {
        return $this->where;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->cloneWith();
        $this->cloneFrom();
        $this->table = clone $this->table;
        foreach ($this->columns as $column => $statement) {
            if (\is_object($statement)) {
                $this->columns[$column] = clone $statement;
            }
        }
        $this->where = clone $this->where;
    }
}
