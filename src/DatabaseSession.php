<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder;

use MakinaCorpus\QueryBuilder\Error\Bridge\TransactionError;
use MakinaCorpus\QueryBuilder\Result\Result;
use MakinaCorpus\QueryBuilder\Transaction\Transaction;

/**
 * Represent an active connected session.
 *
 * Mostly gives you some link state information and executes queries.
 *
 * This is part of bridges, avoiding the hard dependency.
 */
interface DatabaseSession extends QueryBuilder
{
    /**
     * Get current database name.
     */
    public function getCurrentDatabase(): string;

    /**
     * Get default schema name.
     */
    public function getDefaultSchema(): string;

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

    /**
     * Creates a new transaction.
     *
     * @param int $isolationLevel
     *   Default transaction isolation level, it is advised that you set it
     *   directly at this point, since some drivers don't allow isolation
     *   level changes while transaction is started.
     * @param bool $allowPending = true
     *   If set to true, explicitely allow to fetch the currently pending
     *   transaction, else errors will be raised.
     *
     * @throws TransactionError
     *   If you asked a new transaction while another one is opened, or if the
     *   transaction fails starting.
     *
     * @return Transaction
     */
    public function createTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = true): Transaction;

    /**
     * Alias of createTransaction() but it will force it to start
     */
    public function beginTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = true): Transaction;
}
