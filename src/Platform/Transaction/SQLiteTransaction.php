<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Transaction;

class SQLiteTransaction extends AbstractTransaction
{
    #[\Override]
    protected function doTransactionStart(int $isolationLevel): void
    {
        // SQLite transaction are per default all SERIALIZABLE and this cannot
        // be changed.
        // @see https://sqlite.org/isolation.html
        $this->session->executeStatement("BEGIN TRANSACTION");
    }

    #[\Override]
    protected function doChangeLevel(int $isolationLevel): void
    {
        // See comment in doTransactionStart().
    }

    #[\Override]
    protected function doCreateSavepoint(string $name): void
    {
        $this->session->executeStatement("SAVEPOINT ?::id", $name);
    }

    #[\Override]
    protected function doRollbackToSavepoint(string $name): void
    {
        $this->session->executeStatement("ROLLBACK TO SAVEPOINT ?::id", $name);
    }

    #[\Override]
    protected function doRollback(): void
    {
        $this->session->executeStatement("ROLLBACK");
    }

    #[\Override]
    protected function doCommit(): void
    {
        $this->session->executeStatement("COMMIT");
    }

    #[\Override]
    protected function doDeferConstraints(array $constraints): void
    {
        @\trigger_error(\sprintf("SQLite does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }

    #[\Override]
    protected function doDeferAll(): void
    {
        @\trigger_error(\sprintf("SQLite does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }

    #[\Override]
    protected function doImmediateConstraints(array $constraints): void
    {
        @\trigger_error(\sprintf("SQLite does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }

    #[\Override]
    protected function doImmediateAll(): void
    {
        @\trigger_error(\sprintf("SQLite does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }
}
