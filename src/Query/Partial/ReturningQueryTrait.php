<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Query\Partial;

use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Expression\ColumnAll;

/**
 * Represents the RETURNING part of any query.
 *
 * RETURNING is not part of SQL standard as I now of, but a lot of RDBMS
 * implement this, using a custom dialect.
 */
trait ReturningQueryTrait
{
    /** @var SelectColumn[] */
    private array $return = [];

    /**
     * Get select columns array
     *
     * @return SelectColumn[]
     */
    public function getAllReturn(): array
    {
        return $this->return;
    }

    /**
     * Remove everything from the current RETURNING clause.
     */
    public function removeAllReturn(): static
    {
        $this->return = [];

        return $this;
    }

    /**
     * Add a column to RETURNING clause.
     *
     * Expression might carry an alias, it will be override if the $alias
     * parameter is provided, or used otherwised.
     */
    public function returning(mixed $expression = null, ?string $alias = null): static
    {
        if (!$expression || '*' === $expression) {
            if ($alias) {
                throw new QueryBuilderError("RETURNING * cannot be aliased.");
            }
            $expression = new ColumnAll();
        }

        $this->return[] = new SelectColumn(ExpressionHelper::column($expression), $alias);

        return $this;
    }

    /**
     * Add an expression to RETURNING clause.
     *
     * Expression might carry an alias, it will be override if the $alias
     * parameter is provided, or used otherwised.
     */
    public function returningRaw(mixed $expression, ?string $alias = null): static
    {
        $this->return[] = new SelectColumn(ExpressionHelper::raw($expression), $alias);

        return $this;
    }

    /**
     * Find column index for given alias.
     */
    private function findReturnIndex(string $alias): ?string
    {
        return false === ($index = \array_search($alias, $this->return)) ? null : $index;
    }

    /**
     * Remove column from projection.
     */
    public function removeReturn(string $alias): static
    {
        if (null !== ($index = $this->findReturnIndex($alias))) {
            unset($this->return[$index]);
        }
        return $this;
    }

    /**
     * Does this project have the given column.
     */
    public function hasReturn(string $alias): bool
    {
        return (bool) $this->findReturnIndex($alias);
    }

    #[\Override]
    public function willReturnRows(): bool
    {
        return !empty($this->return);
    }
}
