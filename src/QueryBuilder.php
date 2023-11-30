<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Query\Delete;
use MakinaCorpus\QueryBuilder\Query\Insert;
use MakinaCorpus\QueryBuilder\Query\Merge;
use MakinaCorpus\QueryBuilder\Query\RawQuery;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Query\Update;

/**
 * Builds queries, and allow you to forward them to a driver.
 */
interface QueryBuilder
{
    /**
     * Create SELECT query.
     */
    public function select(null|string|Expression $table = null, ?string $alias = null): Select;

    /**
     * Create UPDATE query.
     */
    public function update(string|Expression $table, ?string $alias = null): Update;

    /**
     * Create INSERT query.
     */
    public function insert(string|Expression $table): Insert;

    /**
     * Create MERGE query.
     */
    public function merge(string|Expression $table): Merge;

    /**
     * Create DELETE query.
     */
    public function delete(string|Expression $table, ?string $alias = null): Delete;

    /**
     * Create raw SQL query.
     */
    public function raw(string $expression = null, mixed $arguments = null, bool $returns = false): RawQuery;

    /**
     * Get the expression factory.
     */
    public function expression(): ExpressionFactory;
}
