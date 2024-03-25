<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Expression;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionHelper;
use MakinaCorpus\QueryBuilder\Query\Partial\OrderByTrait;
use MakinaCorpus\QueryBuilder\Query\Partial\OrderByStatement;
use MakinaCorpus\QueryBuilder\Type\Type;

/**
 * Represent a WINDOW.
 *
 * WINDOW can be either expressed using the OVER a column:
 *
 *    SELECT foo() OVER ([WINDOW_EXPR]) FROM bar
 *
 * Or expresed later in the query, for sharing, and referenced in SELECT after
 * the FROM clause:
 *
 *    SELECT foo() OVER (my_window) FROM bar WINDOW my_window AS ([WINDOW_EXPR])
 *
 * This class only formats the ([WINDOW_EXPR]) part of the WINDOW, and will
 * leave the writer the choice how to format things around.
 *
 * Warning: not having the WithAlias interface here is intentional, in order to
 * avoid accidental AS "foo" in SELECT ... OVER (...) statements.
 */
class Window implements Expression
{
    use OrderByTrait;

    public function __construct(
        /** @var OrderByStatement[] */
        ?array $orders = null,
        // @todo validation
        private ?Expression $partitionBy = null,
        private ?string $alias = null,
    ) {
        if ($orders) {
            $this->orders = $orders;
        }
    }

    #[\Override]
    public function returns(): bool
    {
        return false;
    }

    #[\Override]
    public function returnType(): ?Type
    {
        return null;
    }

    /**
     * Get alias.
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * Set PARTITION BY expression.
     *
     * @param callable|string|Expression $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement.
     */
    public function partitionBy(callable|string|Expression $column): static
    {
        $this->partitionBy = ExpressionHelper::column($column);

        return $this;
    }

    /**
     * Get PARTITION BY expression.
     */
    public function getPartitionBy(): ?Expression
    {
        return $this->partitionBy;
    }
}
