<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Transaction;

class SQLiteTransaction extends AbstractTransaction
{
    /**
     * {@inheritdoc}
     */
    protected function doTransactionStart(int $isolationLevel): void
    {
        // SQLite transaction are per default all SERIALIZABLE and this cannot
        // be changed.
        // @see https://sqlite.org/isolation.html
        $this->executor->executeStatement("BEGIN TRANSACTION");
    }

    /**
     * {@inheritdoc}
     */
    protected function doChangeLevel(int $isolationLevel): void
    {
        // See comment in doTransactionStart().
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateSavepoint(string $name): void
    {
        $this->executor->executeStatement("SAVEPOINT ?::identifier", $name);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRollbackToSavepoint(string $name): void
    {
        $this->executor->executeStatement("ROLLBACK TO SAVEPOINT ?::identifier", $name);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRollback(): void
    {
        $this->executor->executeStatement("ROLLBACK");
    }

    /**
     * {@inheritdoc}
     */
    protected function doCommit(): void
    {
        $this->executor->executeStatement("COMMIT");
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferConstraints(array $constraints): void
    {
        @\trigger_error(\sprintf("SQLite does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferAll(): void
    {
        @\trigger_error(\sprintf("SQLite does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateConstraints(array $constraints): void
    {
        @\trigger_error(\sprintf("SQLite does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateAll(): void
    {
        @\trigger_error(\sprintf("SQLite does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }
}
