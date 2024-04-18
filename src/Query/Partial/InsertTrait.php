<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Query\Query;

/**
 * Handles values for INSERT and MERGE queries.
 */
trait InsertTrait
{
    private null|ConstantTable|Query $query = null;
    private bool $queryIsConstantTable = false;
    /** @var string[] */
    private array $columns = [];

    /**
     * Get select columns array.
     *
     * @return string[]
     */
    public function getAllColumns(): array
    {
        return $this->columns;
    }

    /**
     * Add columns.
     *
     * @param string[] $columns
     *   List of columns names.
     */
    public function columns(array $columns): static
    {
        if ($this->columns) {
            throw new QueryBuilderError(\sprintf("You cannot set columns more than once of after calling %s::values().", __CLASS__));
        }

        $this->columns = \array_unique(\array_merge($this->columns, $columns));

        return $this;
    }

    /**
     * Get query.
     */
    public function getQuery(): ConstantTable|Query
    {
        if (!$this->query) {
            throw new QueryBuilderError("Query was not set.");
        }

        return $this->query;
    }

    /**
     * Set SELECT or contant table expression query.
     */
    public function query(ConstantTable|Query $query): static
    {
        if ($this->query) {
            throw new QueryBuilderError(\sprintf("%s::query() was already set.", __CLASS__));
        }
        if ($query instanceof ConstantTable) {
            $this->queryIsConstantTable = true;
        }
        $this->query = $query;

        return $this;
    }

    /**
     * Add a set of values.
     *
     * @param array $values
     *   Either values are numerically indexed, case in which they must match
     *   the internal columns order, or they can be key-value pairs case in
     *   which matching will be dynamically be done.
     */
    public function values(array $values): static
    {
        if (null === $this->query) {
            $this->query = new ConstantTable();
            $this->queryIsConstantTable = true;
        } else if (!$this->queryIsConstantTable) {
            throw new QueryBuilderError(\sprintf("%s::query() and %s::values() are mutually exclusive.", __CLASS__, __CLASS__));
        }

        if (!$this->columns) {
            $this->columns = \array_keys($values);
        }

        $this->query->row($values);

        return $this;
    }
}
