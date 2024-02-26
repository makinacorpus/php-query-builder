<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Error\Bridge\TransactionError;
use MakinaCorpus\QueryBuilder\Result\Result;
use MakinaCorpus\QueryBuilder\Transaction\Transaction;

/**
 * Executes query.
 *
 * This is part of bridges, avoiding the hard dependency.
 */
interface QueryExecutor
{
    /**
     * Execute query and return result.
     */
    public function executeQuery(string|Expression $expression = null, mixed $arguments = null): Result;

    /**
     * Execute query and return affected row count if possible.
     *
     * @return null|int
     *   Affected row count if applyable and driver supports it.
     */
    public function executeStatement(string|Expression $expression = null, mixed $arguments = null): ?int;
}
