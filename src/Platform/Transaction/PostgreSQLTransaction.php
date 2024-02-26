<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Transaction;

use MakinaCorpus\QueryBuilder\Expression\Raw;

class PostgreSQLTransaction extends AbstractTransaction
{
    /**
     * {@inheritdoc}
     */
    protected function doTransactionStart(int $isolationLevel): void
    {
        // Set immediate constraint fail per default to be ISO with
        // backends that don't support deferable constraints
        $this->executor->executeStatement("START TRANSACTION ISOLATION LEVEL ? READ WRITE", new Raw(self::getIsolationLevelString($isolationLevel)));
    }

    /**
     * {@inheritdoc}
     */
    protected function doChangeLevel(int $isolationLevel): void
    {
        // Set immediate constraint fail per default to be ISO with
        // backends that don't support deferable constraints
        $this->executor->executeStatement("SET TRANSACTION ISOLATION LEVEL ?", new Raw(self::getIsolationLevelString($isolationLevel)));
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
        $this->executor->executeStatement("SET CONSTRAINTS ?::identifier[] DEFERRED", $constraints);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferAll(): void
    {
        $this->executor->executeStatement("SET CONSTRAINTS ALL DEFERRED");
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateConstraints(array $constraints): void
    {
        $this->executor->executeStatement("SET CONSTRAINTS ?::identifier[] IMMEDIATE", $constraints);
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateAll(): void
    {
        $this->executor->executeStatement("SET CONSTRAINTS ALL IMMEDIATE");
    }
}
