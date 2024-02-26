<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Functional;

use MakinaCorpus\QueryBuilder\Platform;
use MakinaCorpus\QueryBuilder\Error\Bridge\TransactionError;
use MakinaCorpus\QueryBuilder\Error\Bridge\UniqueConstraintViolationError;
use MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine\DoctrineTestCase;
use MakinaCorpus\QueryBuilder\Transaction\Transaction;
use MakinaCorpus\QueryBuilder\Transaction\TransactionSavepoint;

final class TransactionFunctionalTest extends DoctrineTestCase
{
    /** @before */
    protected function createSchema(): void
    {
        $bridge = $this->getBridge();

        try {
            $bridge->executeStatement(
                <<<SQL
                DROP TABLE transaction_test
                SQL
            );
        } catch (\Throwable) {}

        switch ($bridge->getServerFlavor()) {

            case Platform::MARIADB:
            case Platform::MYSQL:
                $bridge->executeStatement(
                    <<<SQL
                    CREATE TABLE transaction_test (
                        id serial PRIMARY KEY,
                        foo integer NOT NULL,
                        bar varchar(255) DEFAULT NULL
                    )
                    SQL
                );
                $bridge->executeStatement(
                    <<<SQL
                    alter table transaction_test
                        add constraint transaction_test_foo
                        unique (foo)
                    SQL
                );
                $bridge->executeStatement(
                    <<<SQL
                    alter table transaction_test
                        add constraint transaction_test_bar
                        unique (bar)
                    SQL
                );
                break;

            case Platform::SQLSERVER:
                $bridge->executeStatement(
                    <<<SQL
                    CREATE TABLE transaction_test (
                        id serial PRIMARY KEY,
                        foo integer NOT NULL,
                        bar nvarchar(255) DEFAULT NULL
                    )
                    SQL
                );
                $bridge->executeStatement(
                    <<<SQL
                    alter table transaction_test
                        add constraint transaction_test_foo
                        unique (foo)
                        deferrable
                    SQL
                );
                $bridge->executeStatement(
                    <<<SQL
                    alter table transaction_test
                        add constraint transaction_test_bar
                        unique (bar)
                        deferrable
                    SQL
                );
                break;

            default:
                $bridge->executeStatement(
                    <<<SQL
                    CREATE TABLE transaction_test (
                        id serial PRIMARY KEY,
                        foo integer NOT NULL,
                        bar varchar(255) DEFAULT NULL
                    )
                    SQL
                );
                $bridge->executeStatement(
                    <<<SQL
                    alter table transaction_test
                        add constraint transaction_test_foo
                        unique (foo)
                        deferrable
                    SQL
                );
                $bridge->executeStatement(
                    <<<SQL
                    alter table transaction_test
                        add constraint transaction_test_bar
                        unique (bar)
                        deferrable
                    SQL
                );
                break;
        }

        $bridge
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([1, 'a'])
            ->values([2, 'b'])
            ->values([3, 'c'])
            ->executeStatement()
        ;
    }

    /**
     * Normal working transaction.
     */
    public function testTransaction()
    {
        $bridge = $this->getBridge();
        $transaction = $bridge->beginTransaction();

        $bridge
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([4, 'd'])
            ->executeStatement()
        ;

        $transaction->commit();

        $result = $bridge
            ->select('transaction_test')
            ->orderBy('foo')
            ->executeQuery()
        ;

        self::assertSame(4, $result->rowCount());
        self::assertSame('a', $result->fetchRow()->get('bar'));
        self::assertSame('b', $result->fetchRow()->get('bar'));
        self::assertSame('c', $result->fetchRow()->get('bar'));
        self::assertSame('d', $result->fetchRow()->get('bar'));
    }

    public function testNestedTransactionCreatesSavepoint()
    {
        $bridge = $this->getBridge();

        /* if (!$bridge->getPlatform()->supportsTransactionSavepoints()) {
            self::markTestSkipped(\sprintf("Driver '%s' does not supports savepoints", $bridge->getDriverName()));
        } */

        $bridge->delete('transaction_test')->executeStatement();

        $transaction = $bridge->beginTransaction();

        $bridge
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([789, 'f'])
            ->executeStatement()
        ;

        $savepoint = $bridge->beginTransaction();

        self::assertInstanceOf(TransactionSavepoint::class, $savepoint);
        self::assertTrue($savepoint->isNested());
        self::assertNotNull($savepoint->getSavepointName());

        $bridge
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([456, 'g'])
            ->executeStatement()
        ;

        $transaction->commit();

        $result = $bridge
            ->select('transaction_test')
            ->orderBy('foo')
            ->executeQuery()
        ;

        self::assertSame(2, $result->rowCount());
        self::assertSame('g', $result->fetchRow()->get('bar'));
        self::assertSame('f', $result->fetchRow()->get('bar'));
    }

    public function testNestedTransactionRollbackToSavepointTransparently()
    {
        $bridge = $this->getBridge();

        /* if (!$bridge->getPlatform()->supportsTransactionSavepoints()) {
            self::markTestSkipped(\sprintf("Driver '%s' does not supports savepoints", $bridge->getDriverName()));
        } */

        $bridge->delete('transaction_test')->executeStatement();

        $transaction = $bridge->beginTransaction();

        $bridge
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([789, 'f'])
            ->executeStatement()
        ;

        $savepoint = $bridge->beginTransaction();

        self::assertInstanceOf(TransactionSavepoint::class, $savepoint);
        self::assertTrue($savepoint->isNested());
        self::assertNotNull($savepoint->getSavepointName());

        $bridge
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([456, 'g'])
            ->executeStatement()
        ;

        $savepoint->rollback();
        $transaction->commit();

        $result = $bridge
            ->select('transaction_test')
            ->orderBy('foo')
            ->executeQuery()
        ;

        self::assertSame(1, $result->rowCount());
        self::assertSame('f', $result->fetchRow()->get('bar'));
    }

    /**
     * Fail with immediate constraints (not deferred).
     */
    public function testImmediateTransactionFail()
    {
        $bridge = $this->getBridge();

        $transaction = $bridge
            ->beginTransaction()
            ->deferred() // Defer all
            ->immediate('transaction_test_bar')
        ;

        try {
            // This should pass, foo constraint it deferred;
            // if backend does not support defering, this will
            // fail anyway, but the rest of the test is still
            // valid
            $bridge
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->executeStatement()
            ;

            // This should fail, bar constraint it immediate
            $bridge
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->executeStatement()
            ;

            self::fail();

        } catch (TransactionError $e) {
            self::assertInstanceOf(UniqueConstraintViolationError::class, $e->getPrevious());
        } finally {
            if ($transaction->isStarted()) {
                $transaction->rollback();
            }
        }
    }

    /**
     * Fail with deferred constraints.
     */
    public function testDeferredTransactionFail()
    {
        $bridge = $this->getBridge();

        /* if (!$bridge->getPlatform()->supportsDeferingConstraints()) {
            self::markTestSkipped("driver does not support defering constraints");
        } */

        $transaction = $bridge
            ->beginTransaction()
            ->immediate() // Immediate all
            ->deferred('transaction_test_foo')
        ;

        try {

            // This should pass, foo constraint it deferred
            $bridge
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->executeStatement()
            ;

            // This should fail, bar constraint it immediate
            $bridge
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->executeStatement()
            ;

            self::fail();

        } catch (TransactionError $e) {
            self::assertInstanceOf(UniqueConstraintViolationError::class, $e->getPrevious());
        } finally {
            if ($transaction->isStarted()) {
                $transaction->rollback();
            }
        }

        self::assertTrue(true);
    }

    /**
     * Fail with ALL constraints deferred.
     */
    public function testDeferredAllTransactionFail()
    {
        $bridge = $this->getBridge();

        /* if (!$bridge->getPlatform()->supportsDeferingConstraints()) {
            self::markTestSkipped("driver does not support defering constraints");
        } */

        $transaction = $bridge
            ->beginTransaction()
            ->deferred()
        ;

        try {

            // This should pass, all are deferred
            $bridge
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->executeStatement()
            ;

            // This should pass, all are deferred
            $bridge
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->executeStatement()
            ;

            $transaction->commit();

        } catch (TransactionError $e) {
            self::assertInstanceOf(UniqueConstraintViolationError::class, $e->getPrevious());
        } finally {
            if ($transaction->isStarted()) {
                $transaction->rollback();
            }
        }

        self::assertTrue(true);
    }

    /**
     * Tests that rollback works.
     */
    public function testTransactionRollback()
    {
        $bridge = $this->getBridge();

        $transaction = $bridge->beginTransaction();

        $bridge
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([4, 'd'])
            ->executeStatement()
        ;

        $transaction->rollback();

        $result = $bridge
            ->select('transaction_test')
            ->executeQuery()
        ;

        self::assertSame(3, $result->rowCount());
    }

    /**
     * Test that fetching a pending transaction is disallowed.
     */
    public function testPendingAllowed()
    {
        $bridge = $this->getBridge();

        $transaction = $bridge->beginTransaction();

        // Fetch another transaction, it should fail
        try {
            $bridge->beginTransaction(Transaction::REPEATABLE_READ, false);
            self::fail();
        } catch (TransactionError $e) {
        }

        // Fetch another transaction, it should NOT fail
        $t3 = $bridge->beginTransaction(Transaction::REPEATABLE_READ, true);
        // @todo temporary deactivating this test since that the profiling
        //   transaction makes it harder
        //self::assertSame($t3, $transaction);
        self::assertTrue($t3->isStarted());

        // Force rollback of the second, ensure previous is stopped too
        $t3->rollback();
        self::assertFalse($t3->isStarted());
        // Still true, because we acquired a savepoint
        self::assertTrue($transaction->isStarted());

        $transaction->rollback();
        self::assertFalse($transaction->isStarted());
    }

    /**
     * Test the savepoint feature.
     */
    public function testTransactionSavepoint()
    {
        $bridge = $this->getBridge();

        $transaction = $bridge->beginTransaction();

        $bridge
            ->update('transaction_test')
            ->set('bar', 'z')
            ->where('foo', 1)
            ->executeStatement()
        ;

        $transaction->savepoint('bouyaya');

        $bridge
            ->update('transaction_test')
            ->set('bar', 'y')
            ->where('foo', 2)
            ->executeStatement()
        ;

        $transaction->rollbackToSavepoint('bouyaya');
        $transaction->commit();

        $oneBar = $bridge
            ->select('transaction_test')
            ->column('bar')
            ->where('foo', 1)
            ->executeQuery()
            ->fetchOne()
        ;
        // This should have pass since it's before the savepoint
        self::assertSame('z', $oneBar);

        $twoBar = $bridge
            ->select('transaction_test')
            ->column('bar')
            ->where('foo', 2)
            ->executeQuery()
            ->fetchOne()
        ;
        // This should not have pass thanks to savepoint
        self::assertSame('b', $twoBar);
    }
}
