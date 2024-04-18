<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Transaction;

use MakinaCorpus\QueryBuilder\Error\Server\TransactionError;

interface Transaction
{
    public const READ_UNCOMMITED = 1;
    public const READ_COMMITED = 2;
    public const REPEATABLE_READ = 3;
    public const SERIALIZABLE = 4;

    /**
     * Change transaction level.
     *
     * @param int $isolationLevel
     *   One of the Transaction::* constants.
     */
    public function level(int $isolationLevel): static;

    /**
     * Is transaction started.
     */
    public function isStarted(): bool;

    /**
     * Start the transaction.
     */
    public function start(): static;

    /**
     * Set as immediate all or a set of constraints.
     *
     * @param string|string[] $constraint
     *   If set to null, all constraints are set immediate
     *   If a string or a string array, only the given constraint
     *   names are set as immediate.
     */
    public function immediate(string|array $constraint = null): static;

    /**
     * Defer all or a set of constraints.
     *
     * @param string|string[] $constraint
     *   If set to null, all constraints are set immediate
     *   If a string or a string array, only the given constraint
     *   names are set as immediate.
     */
    public function deferred(string|array $constraint = null): static;

    /**
     * Creates a savepoint and return its name.
     *
     * @param string $name
     *   Optional user given savepoint name, if none provided a name will be
     *   automatically computed using a serial.
     *
     * @throws TransactionError
     *   If savepoint name already exists.
     *
     * @return TransactionSavepoint
     *   The nested transaction.
     */
    public function savepoint(string $name = null): TransactionSavepoint;

    /**
     * Is transaction nested (ie. is a savepoint).
     */
    public function isNested(): bool;

    /**
     * Get transaction generated name.
     */
    public function getName(): string;

    /**
     * Get savepoint name, if transaction is a savepoint, null otherwise.
     */
    public function getSavepointName(): ?string;

    /**
     * Explicit transaction commit.
     */
    public function commit(): static;

    /**
     * Explicit transaction rollback.
     */
    public function rollback(): static;

    /**
     * Rollback to savepoint.
     *
     * @param string $name
     *   Savepoint name.
     */
    public function rollbackToSavepoint(string $name): static;
}
