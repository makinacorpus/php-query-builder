<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Browser;

use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\AbstractChange;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\AbstractCondition;
use MakinaCorpus\QueryBuilder\Schema\Diff\SchemaTransaction;
use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
use MakinaCorpus\QueryBuilder\Transaction\Transaction;

class SchemaWriterLogVisitor extends ChangeLogVisitor
{
    private ?Transaction $transaction = null;

    public function __construct(
        private SchemaManager $schema,
    ) {}

    private function dieIfNoTransaction(): void
    {
        if ($this->schema->supportsTransaction() && (!$this->transaction || !$this->transaction->isStarted())) {
            throw new QueryBuilderError("Transaction should have been started here.");
        }
    }

    private function dieIfTransaction(): void
    {
        if ($this->schema->supportsTransaction() && $this->transaction && !$this->transaction->isStarted()) {
            throw new QueryBuilderError("Transaction should have not been started here.");
        }
    }

    #[\Override]
    public function start(SchemaTransaction $transaction): void
    {
        $this->dieIfTransaction();

        if ($this->schema->supportsTransaction()) {
            $this->transaction = $this->schema->getDatabaseSession()->beginTransaction(Transaction::SERIALIZABLE);
        }
    }

    #[\Override]
    public function stop(SchemaTransaction $transaction): void
    {
        $this->dieIfNoTransaction();

        if ($this->schema->supportsTransaction()) {
            try {
                $this->transaction->commit();
            } finally {
                $this->transaction = null;
            }
        }
    }

    #[\Override]
    public function error(SchemaTransaction $transaction, \Throwable $error): void
    {
        $this->dieIfNoTransaction();

        if ($this->schema->supportsTransaction()) {
            try {
                $this->transaction->rollback();
            } finally {
                $this->transaction = null;
            }
        }
    }

    #[\Override]
    public function evaluate(AbstractCondition $condition): bool
    {
        $this->dieIfNoTransaction();

        return $this->schema->evaluateCondition($condition);
    }

    #[\Override]
    public function apply(AbstractChange $change): void
    {
        $this->dieIfNoTransaction();

        $this->schema->applyChange($change);
    }
}
