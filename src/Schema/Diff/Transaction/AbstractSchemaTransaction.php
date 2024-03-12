<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Transaction;

use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\CallbackChange;
use MakinaCorpus\QueryBuilder\Schema\Diff\ChangeLog;
use MakinaCorpus\QueryBuilder\Schema\Diff\ChangeLogItem;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\AbstractCondition;

// @todo IDE bug.
\class_exists(QueryBuilder::class);

/**
 * @internal
 *   Exists because PHP has no genericity.
 */
abstract class AbstractSchemaTransaction extends GeneratedAbstractTransaction
{
    private ChangeLog $changeLog;

    public function __construct(
        string $database,
        string $schema,
    ) {
        parent::__construct($database, $schema);

        $this->changeLog = new ChangeLog();
    }

    /**
     * Get current change log.
     */
    public function getChangeLog(): ChangeLog
    {
        return $this->changeLog;
    }

    /**
     * Execute a user callback.
     *
     * @param (callable(QueryBuilder):mixed) $callback
     *   Callback result will be ignored.
     */
    public function query(callable $callback): static
    {
        $this->logChange(new CallbackChange($this->database, $this->schema, $callback));

        return $this;
    }

    /**
     * Create nested instance with given conditions.
     */
    protected function nestWithCondition(AbstractCondition ...$conditions): NestedSchemaTransaction|DeepNestedSchemaTransaction
    {
        if ($this instanceof NestedSchemaTransaction || $this instanceof DeepNestedSchemaTransaction) {
            $ret = new DeepNestedSchemaTransaction($this, $this->database, $this->schema, $conditions);
        } else {
            $ret = new NestedSchemaTransaction($this, $this->database, $this->schema, $conditions);
        }

        $this->logChange($ret);

        return $ret;
    }

    #[\Override]
    public function logChange(ChangeLogItem $change): void
    {
        $this->changeLog->add($change);
    }
}
