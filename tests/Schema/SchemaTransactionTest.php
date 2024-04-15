<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Schema;

use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\Schema\Diff\SchemaTransaction;
use MakinaCorpus\QueryBuilder\Schema\Diff\Browser\ChangeLogBrowser;
use MakinaCorpus\QueryBuilder\Schema\Diff\Browser\ChangeLogVisitor;
use MakinaCorpus\QueryBuilder\Schema\Diff\Change\AbstractChange;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\AbstractCondition;
use MakinaCorpus\QueryBuilder\Schema\Diff\Condition\ColumnExists;
use MakinaCorpus\QueryBuilder\Schema\Diff\Transaction\AbstractNestedSchemaTransaction;
use PHPUnit\Framework\TestCase;

class SchemaTransactionTest extends TestCase
{
    public function testNesting(): void
    {
        $transaction = new SchemaTransaction('some_schema', fn () => null);

        $transaction
            ->ifTableNotExists('users')
                ->createTable('users')
                    ->column('id', 'serial', false)
                    ->primaryKey(['id'])
                ->endTable()
                ->query(fn (QueryBuilder $queryBuilder) => true)
            ->endIf()
            ->ifCallback(fn (QueryBuilder $queryBuilder) => true)
                ->dropTable('foo')
            ->endIf()
            ->ifColumnNotExists('users', 'email')
                ->ifColumnNotExists('users', 'email')
                    ->addColumn('users', 'email', 'text', true)
                    ->query(fn (QueryBuilder $queryBuilder) => true)
                ->endIf()
                ->ifColumnExists('users', 'skip')
                    ->addColumn('users', 'email', 'text', true)
                ->endIf()
                ->addUniqueKey('users', ['email'])
            ->endIf()
            ->query(fn (QueryBuilder $queryBuilder) => null)
            ->addColumn('users', 'name', 'text', false)
            ->commit()
        ;

        $visitor = new class () extends ChangeLogVisitor {
            private array $lines = [];

            #[\Override]
            public function enter(AbstractNestedSchemaTransaction $nested, int $depth): void
            {
                $this->lines[] = "Entering level " . $depth;
            }

            #[\Override]
            public function leave(AbstractNestedSchemaTransaction $nested, int $depth): void
            {
                $this->lines[] = "Exiting level " . $depth;
            }

            #[\Override]
            public function skip(AbstractNestedSchemaTransaction $nested, int $depth): void
            {
                $this->lines[] = "Skipping level " . $depth;
            }

            #[\Override]
            public function evaluate(AbstractCondition $condition): bool
            {
                if ($condition instanceof ColumnExists && 'skip' === $condition->getColumn()) {
                    return false;
                }
                return true;
            }

            #[\Override]
            public function apply(AbstractChange $change): void
            {
                $this->lines[] = "Applying " . \get_class($change);
            }

            public function getOutput(): string
            {
                return \implode("\n", $this->lines);
            }
        };

        $browser = new ChangeLogBrowser();
        $browser->addVisitor($visitor);
        $browser->browse($transaction);

        self::assertSame(
            $visitor->getOutput(),
            <<<EOT
            Entering level 1
            Applying MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableCreate
            Applying MakinaCorpus\QueryBuilder\Schema\Diff\Change\CallbackChange
            Exiting level 1
            Entering level 1
            Applying MakinaCorpus\QueryBuilder\Schema\Diff\Change\TableDrop
            Exiting level 1
            Entering level 1
            Entering level 2
            Applying MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnAdd
            Applying MakinaCorpus\QueryBuilder\Schema\Diff\Change\CallbackChange
            Exiting level 2
            Skipping level 2
            Applying MakinaCorpus\QueryBuilder\Schema\Diff\Change\UniqueKeyAdd
            Exiting level 1
            Applying MakinaCorpus\QueryBuilder\Schema\Diff\Change\CallbackChange
            Applying MakinaCorpus\QueryBuilder\Schema\Diff\Change\ColumnAdd
            EOT,
        );
    }
}
