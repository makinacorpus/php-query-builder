<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Platform\Transaction;

use MakinaCorpus\QueryBuilder\Error\Server\ServerError;
use MakinaCorpus\QueryBuilder\Expression\Raw;

class MySQLTransaction extends AbstractTransaction
{
    #[\Override]
    protected function doTransactionStart(int $isolationLevel): void
    {
        $levelString = self::getIsolationLevelString($isolationLevel);

        try {
            // Transaction level cannot be changed while in the transaction,
            // so it must set before starting the transaction
            $this->session->executeStatement("SET TRANSACTION ISOLATION LEVEL ?", new Raw($levelString));
        } catch (ServerError $e) {
            // Gracefully continue without changing the transaction isolation
            // level, MySQL don't support it, but we cannot penalize our users;
            // beware that users might use a transaction with a lower level
            // than they asked for, and data consistency is not ensured anymore
            // that's the downside of using MySQL.
            if (1568 == $e->getCode()) {
                // @todo if debug
                @\trigger_error(\sprintf("Transaction '%s' is nested into another, MySQL can't change the isolation level '%s'", $this->getName(), $levelString), E_USER_NOTICE);
            }

            throw $e;
        }

        $this->session->executeStatement("BEGIN");
    }

    #[\Override]
    protected function doChangeLevel(int $isolationLevel): void
    {
        // @todo if debug
        @\trigger_error(\sprintf("MySQL does not support transaction level change during transaction '%s'", $this->getName()), E_USER_NOTICE);
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
        // @todo if debug
        @\trigger_error(\sprintf("MySQL does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }

    #[\Override]
    protected function doDeferAll(): void
    {
        // @todo if debug
        @\trigger_error(\sprintf("MySQL does not support deferred constraints during transaction '%s'", $this->getName()), E_USER_NOTICE);
    }

    #[\Override]
    protected function doImmediateConstraints(array $constraints): void
    {
        // Do nothing, as MySQL always check constraints immediatly
    }

    #[\Override]
    protected function doImmediateAll(): void
    {
        // Do nothing, as MySQL always check constraints immediatly
    }
}
