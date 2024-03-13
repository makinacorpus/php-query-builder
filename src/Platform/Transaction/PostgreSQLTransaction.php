<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Transaction;

use MakinaCorpus\QueryBuilder\Expression\Raw;

class PostgreSQLTransaction extends AbstractTransaction
{
    #[\Override]
    protected function doTransactionStart(int $isolationLevel): void
    {
        // Set immediate constraint fail per default to be ISO with
        // backends that don't support deferable constraints
        $this->executor->executeStatement("START TRANSACTION ISOLATION LEVEL ? READ WRITE", new Raw(self::getIsolationLevelString($isolationLevel)));
    }

    #[\Override]
    protected function doChangeLevel(int $isolationLevel): void
    {
        // Set immediate constraint fail per default to be ISO with
        // backends that don't support deferable constraints
        $this->executor->executeStatement("SET TRANSACTION ISOLATION LEVEL ?", new Raw(self::getIsolationLevelString($isolationLevel)));
    }

    #[\Override]
    protected function doCreateSavepoint(string $name): void
    {
        $this->executor->executeStatement("SAVEPOINT ?::id", $name);
    }

    #[\Override]
    protected function doRollbackToSavepoint(string $name): void
    {
        $this->executor->executeStatement("ROLLBACK TO SAVEPOINT ?::id", $name);
    }

    #[\Override]
    protected function doRollback(): void
    {
        $this->executor->executeStatement("ROLLBACK");
    }

    #[\Override]
    protected function doCommit(): void
    {
        $this->executor->executeStatement("COMMIT");
    }

    #[\Override]
    protected function doDeferConstraints(array $constraints): void
    {
        $this->executor->executeStatement("SET CONSTRAINTS ?::id[] DEFERRED", $constraints);
    }

    #[\Override]
    protected function doDeferAll(): void
    {
        $this->executor->executeStatement("SET CONSTRAINTS ALL DEFERRED");
    }

    #[\Override]
    protected function doImmediateConstraints(array $constraints): void
    {
        $this->executor->executeStatement("SET CONSTRAINTS ?::id[] IMMEDIATE", $constraints);
    }

    #[\Override]
    protected function doImmediateAll(): void
    {
        $this->executor->executeStatement("SET CONSTRAINTS ALL IMMEDIATE");
    }
}
