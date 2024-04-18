<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Transaction;

use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Error\Server\TransactionError;
use MakinaCorpus\QueryBuilder\Transaction\Transaction;
use MakinaCorpus\QueryBuilder\Transaction\TransactionSavepoint;

/**
 * Base implementation of the Transaction interface that prevents logic errors.
 */
abstract class AbstractTransaction implements Transaction
{
    /** Default savepoint name prefix */
    public const SAVEPOINT_PREFIX = 'gsp_';

    private bool $started = false;
    private int $count = 1;
    private int $isolationLevel = self::REPEATABLE_READ;
    private int $savepoint = 0;
    /** @var string[] */
    private array $savepoints = [];

    final public function __construct(
        protected readonly DatabaseSession $session,
        int $isolationLevel = self::REPEATABLE_READ
    ) {
        $this->level($isolationLevel);
    }

    /**
     * Get transaction level string.
     */
    public static function getIsolationLevelString(int $isolationLevel): string
    {
        return match ($isolationLevel) {
            Transaction::READ_UNCOMMITED => 'READ UNCOMMITTED',
            Transaction::READ_COMMITED => 'READ COMMITTED',
            Transaction::REPEATABLE_READ => 'REPEATABLE READ',
            Transaction::SERIALIZABLE => 'SERIALIZABLE',
            default => throw new TransactionError(\sprintf("%s: unknown transaction level", $isolationLevel)),
        };
    }

    /**
     * Started transactions should not be left opened, this will force a
     * transaction rollback and throw an exception.
     */
    final public function __destruct()
    {
        if ($this->started) {
            $this->rollback();

            throw new TransactionError(\sprintf("transactions should never be left opened"));
        }
    }

    /**
     * Starts the transaction.
     */
    abstract protected function doTransactionStart(int $isolationLevel): void;

    /**
     * Change transaction level.
     */
    abstract protected function doChangeLevel(int $isolationLevel): void;

    /**
     * Create savepoint.
     */
    abstract protected function doCreateSavepoint(string $name): void;

    /**
     * Rollback to savepoint.
     */
    abstract protected function doRollbackToSavepoint(string $name): void;

    /**
     * Rollback.
     */
    abstract protected function doRollback(): void;

    /**
     * Commit.
     */
    abstract protected function doCommit(): void;

    /**
     * Defer given constraints.
     *
     * @param string[] $constraints
     *   Constraint name list.
     */
    abstract protected function doDeferConstraints(array $constraints): void;

    /**
     * Defer all constraints.
     */
    abstract protected function doDeferAll(): void;

    /**
     * Set given constraints as immediate.
     *
     * @param string[] $constraints
     *   Constraint name list.
     */
    abstract protected function doImmediateConstraints(array $constraints): void;

    /**
     * Set all constraints as immediate.
     */
    abstract protected function doImmediateAll(): void;

    #[\Override]
    public function level(int $isolationLevel): static
    {
        if ($isolationLevel === $this->isolationLevel) {
            return $this; // Nothing to be done
        }

        if ($this->started) {
            $this->doChangeLevel($isolationLevel);
        }

        return $this;
    }

    #[\Override]
    public function isStarted(): bool
    {
        return $this->started;
    }

    #[\Override]
    public function start(): static
    {
        if (!$this->started) {
            $this->doTransactionStart($this->isolationLevel);
            $this->started = true;
        }

        return $this;
    }

    #[\Override]
    public function immediate($constraint = null): static
    {
        if ($constraint) {
            if (!\is_array($constraint)) {
                $constraint = [$constraint];
            }
            $this->doImmediateConstraints($constraint);
        } else {
            $this->doImmediateAll();
        }

        return $this;
    }

    #[\Override]
    public function deferred($constraint = null): static
    {
        if ($constraint) {
            if (!\is_array($constraint)) {
                $constraint = [$constraint];
            }
            $this->doDeferConstraints($constraint);
        } else {
            $this->doDeferAll();
        }

        return $this;
    }

    #[\Override]
    public function savepoint(string $name = null): TransactionSavepoint
    {
        if (!$this->started) {
            throw new TransactionError(\sprintf("can not commit a non-running transaction"));
        }

        if (!$name) {
            $name = self::SAVEPOINT_PREFIX.(++$this->savepoint);
        }

        if (isset($this->savepoints[$name])) {
            throw new TransactionError(\sprintf("%s: savepoint already exists", $name));
        }

        $this->doCreateSavepoint($name);

        return $this->savepoints[$name] = new TransactionSavepoint($name, $this);
    }

    #[\Override]
    public function isNested(): bool
    {
        return false;
    }

    #[\Override]
    public function getName(): string
    {
        return (string) $this->count;
    }

    #[\Override]
    public function getSavepointName(): ?string
    {
        return null;
    }

    #[\Override]
    public function commit(): static
    {
        if (!$this->started) {
            throw new TransactionError(\sprintf("can not commit a non-running transaction"));
        }

        $this->doCommit();

        // This code will be reached only if the commit failed, the transaction
        // not beeing stopped at the application level allows you to call
        // rollbacks later.
        $this->started = false;

        return $this;
    }

    #[\Override]
    public function rollback(): static
    {
        if (!$this->started) {
            throw new TransactionError(\sprintf("can not rollback a non-running transaction"));
        }

        // Even if the rollback fails and throw exceptions, this transaction
        // is dead in the woods, just mark it as stopped.
        $this->started = false;

        $this->doRollback();

        return $this;
    }

    #[\Override]
    public function rollbackToSavepoint(string $name): static
    {
        if (!$this->started) {
            throw new TransactionError(\sprintf("can not rollback to savepoint in a non-running transaction"));
        }
        if (!isset($this->savepoints[$name])) {
            throw new TransactionError(\sprintf("%s: savepoint does not exists or is not handled by this object", $name));
        }
        if (!$this->savepoints[$name]) {
            throw new TransactionError(\sprintf("%s: savepoint was already rollbacked", $name));
        }

        $this->doRollbackToSavepoint($name);

        return $this;
    }
}
