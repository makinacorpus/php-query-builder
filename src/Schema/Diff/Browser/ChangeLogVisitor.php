<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Browser;

use MakinaCorpus\QueryBuilder\Schema\Diff\Change\AbstractChange;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\AbstractCondition;
use MakinaCorpus\QueryBuilder\Schema\Diff\SchemaTransaction;
use MakinaCorpus\QueryBuilder\Schema\Diff\Transaction\AbstractNestedSchemaTransaction;

abstract class ChangeLogVisitor
{
    /**
     * Process starts.
     */
    public function start(SchemaTransaction $transaction): void
    {
    }

    /**
     * Process ends.
     */
    public function stop(SchemaTransaction $transaction): void
    {
    }

    /**
     * Process ends due to an error.
     */
    public function error(SchemaTransaction $transaction, \Throwable $error): void
    {
    }

    /**
     * Enter nesting level.
     */
    public function enter(AbstractNestedSchemaTransaction $nested, int $depth): void
    {
    }

    /**
     * Leave nesting level.
     */
    public function leave(AbstractNestedSchemaTransaction $nested, int $depth): void
    {
    }

    /**
     * Skip nesting level.
     */
    public function skip(AbstractNestedSchemaTransaction $nested, int $depth): void
    {
    }

    /**
     * Evaluate a single condition.
     */
    public function evaluate(AbstractCondition $condition): bool
    {
        return true;
    }

    /**
     * Apply a single change.
     */
    public function apply(AbstractChange $change): void
    {
    }
}
