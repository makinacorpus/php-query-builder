<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Schema\Diff\Browser;

use MakinaCorpus\QueryBuilder\Schema\Diff\Change\AbstractChange;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\AbstractCondition;
use MakinaCorpus\QueryBuilder\Schema\Diff\SchemaTransaction;
use MakinaCorpus\QueryBuilder\Schema\Diff\Transaction\AbstractNestedSchemaTransaction;

class ChangeLogBrowser
{
    /** @var ChangeLogVisitor[] */
    private array $visitors = [];

    public function addVisitor(ChangeLogVisitor $visitor): void
    {
        $this->visitors[] = $visitor;
    }

    public function browse(SchemaTransaction $transaction): void
    {
        try {
            foreach ($this->visitors as $visitor) {
                $visitor->start($transaction);
            }

            foreach ($transaction->getChangeLog()->getAll() as $change) {
                if ($change instanceof AbstractNestedSchemaTransaction) {
                    $this->reduceNested($change, 1, $this->visitors);
                } else if ($change instanceof AbstractChange) {
                    foreach ($this->visitors as $visitor) {
                        $visitor->apply($change);
                    }
                }
            }

            foreach ($this->visitors as $visitor) {
                $visitor->stop($transaction);
            }
        } catch (\Throwable $error) {
            foreach ($this->visitors as $visitor) {
                $visitor->error($transaction, $error);
            }

            throw $error;
        }
    }

    /**
     * @param AbstractCondition[] $conditions
     */
    protected function evaluateConditions(array $conditions, ChangeLogVisitor $visitor): bool
    {
        foreach ($conditions as $condition) {
            if (!$visitor->evaluate($condition)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param ChangeLogVisitor[] $visitors
     */
    protected function reduceNested(AbstractNestedSchemaTransaction $new, int $depth, array $visitors)
    {
        if ($conditions = $new->getConditions()) {
            $candidates = [];

            foreach ($visitors as $visitor) {
                \assert($visitor instanceof ChangeLogVisitor);

                if ($this->evaluateConditions($conditions, $visitor)) {
                    $candidates[] = $visitor;
                } else {
                    $visitor->skip($new, $depth);
                }
            }

            if ($candidates) {
                $this->browseNested($new, $depth, $candidates);
            }
        }
    }

    /**
     * @param ChangeLogVisitor[] $visitors
     */
    protected function browseNested(AbstractNestedSchemaTransaction $new, int $depth, array $visitors): void
    {
        foreach ($visitors as $visitor) {
            $visitor->enter($new, $depth);
        }

        foreach ($new->getChangeLog()->getAll() as $change) {
            if ($change instanceof AbstractNestedSchemaTransaction) {
                $this->reduceNested($change, $depth + 1, $visitors);
            } else if ($change instanceof AbstractChange) {
                foreach ($visitors as $visitor) {
                    $visitor->apply($change);
                }
            }
        }

        foreach ($visitors as $visitor) {
            $visitor->leave($new, $depth);
        }
    }
}
