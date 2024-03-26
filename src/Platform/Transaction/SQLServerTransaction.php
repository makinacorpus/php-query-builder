<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Transaction;

use MakinaCorpus\QueryBuilder\Expression\Raw;

class SQLServerTransaction extends AbstractTransaction
{
    #[\Override]
    protected function doTransactionStart(int $isolationLevel): void
    {
        $this->doChangeLevel($isolationLevel);
        $this->session->executeStatement("BEGIN TRANSACTION");
    }

    #[\Override]
    protected function doChangeLevel(int $isolationLevel): void
    {
        $this->session->executeStatement("SET TRANSACTION ISOLATION LEVEL ?", new Raw(self::getIsolationLevelString($isolationLevel)));
    }

    #[\Override]
    protected function doCreateSavepoint(string $name): void
    {
        $this->session->executeStatement("SAVE TRANSACTION ?::id", $name);
    }

    #[\Override]
    protected function doRollbackToSavepoint(string $name): void
    {
        $this->session->executeStatement("ROLLBACK TRANSACTION ?::id", $name);
    }

    #[\Override]
    protected function doRollback(): void
    {
        $this->session->executeStatement("ROLLBACK TRANSACTION");
    }

    #[\Override]
    protected function doCommit(): void
    {
        $this->session->executeStatement("COMMIT TRANSACTION");
    }

    #[\Override]
    protected function doDeferConstraints(array $constraints): void
    {
        @\trigger_error(\sprintf("SQL Server does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }

    #[\Override]
    protected function doDeferAll(): void
    {
        @\trigger_error(\sprintf("SQL Server does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }

    #[\Override]
    protected function doImmediateConstraints(array $constraints): void
    {
        @\trigger_error(\sprintf("SQL Server does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }

    #[\Override]
    protected function doImmediateAll(): void
    {
        @\trigger_error(\sprintf("SQL Server does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }
}
