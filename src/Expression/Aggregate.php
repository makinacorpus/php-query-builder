<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Where;

/**
 * Represent an aggregate SELECT column clause.
 */
class Aggregate implements Expression
{
    private ?Where $filter = null;

    public function __construct(
        private string $functionName,
        private ?Expression $column = null,
        ?Expression $filter = null,
        private null|Window|Identifier $over = null,
    ) {
        if ($filter) {
            if ($filter instanceof Where) {
                $this->filter = $filter;
            } else {
                $this->filter = new Where();
                $this->filter->with($filter);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function returns(): bool
    {
        return true;
    }

    /**
     * Get aggregate function name.
     */
    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    /**
     * Get expression in aggregate function call.
     */
    public function getColumn(): ?Expression
    {
        return $this->column;
    }

    /**
     * Get FILTER () clause, as a Where instance.
     */
    public function getFilter(): ?Where
    {
        if ($this->filter && !$this->filter->isEmpty()) {
            return $this->filter;
        }
        return null;
    }

    /**
     * Get OVER (WINDOW) clause, if any. It can be an identifier if WINDOW
     * is written later in SELECT query.
     */
    public function getOverWindow(): null|Window|Identifier
    {
        return $this->over;
    }
}
