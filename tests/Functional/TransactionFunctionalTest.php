<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Functional;

use MakinaCorpus\QueryBuilder\Error\Server\TransactionError;
use MakinaCorpus\QueryBuilder\Error\Server\UniqueConstraintViolationError;
use MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine\DoctrineTestCase;
use MakinaCorpus\QueryBuilder\Transaction\Transaction;
use MakinaCorpus\QueryBuilder\Transaction\TransactionSavepoint;
use MakinaCorpus\QueryBuilder\Vendor;

final class TransactionFunctionalTest extends DoctrineTestCase
{
    /** @before */
    protected function createSchema(): void
    {
        $session = $this->getDatabaseSession();

        try {
            $session->executeStatement(
                <<<SQL
                DROP TABLE transaction_test
                SQL
            );
        } catch (\Throwable) {}

        switch ($session->getVendorName()) {

            case Vendor::MARIADB:
            case Vendor::MYSQL:
                $session->executeStatement(
                    <<<SQL
                    CREATE TABLE transaction_test (
                        id serial PRIMARY KEY,
                        foo integer NOT NULL,
                        bar varchar(255) DEFAULT NULL
                    )
                    SQL
                );
                $session->executeStatement(
                    <<<SQL
                    alter table transaction_test
                        add constraint transaction_test_foo
                        unique (foo)
                    SQL
                );
                $session->executeStatement(
                    <<<SQL
                    alter table transaction_test
                        add constraint transaction_test_bar
                        unique (bar)
                    SQL
                );
                break;

            case Vendor::SQLSERVER:
                $session->executeStatement(
                    <<<SQL
                    CREATE TABLE transaction_test (
                        id int IDENTITY(1,1) PRIMARY KEY,
                        foo integer NOT NULL,
                        bar nvarchar(255) DEFAULT NULL
                    )
                    SQL
                );
                $session->executeStatement(
                    <<<SQL
                    alter table transaction_test
                        add constraint transaction_test_foo
                        unique (foo)
                    SQL
                );
                $session->executeStatement(
                    <<<SQL
                    alter table transaction_test
                        add constraint transaction_test_bar
                        unique (bar)
                    SQL
                );
                break;

            case Vendor::SQLITE:
                $session->executeStatement(
                    <<<SQL
                    CREATE TABLE transaction_test (
                        id serial PRIMARY KEY,
                        foo integer NOT NULL,
                        bar nvarchar(255) DEFAULT NULL
                    )
                    SQL
                );
                // No UNIQUE DEFERRABLE for SQLite.
                break;

            default:
                $session->executeStatement(
                    <<<SQL
                    CREATE TABLE transaction_test (
                        id serial PRIMARY KEY,
                        foo integer NOT NULL,
                        bar varchar(255) DEFAULT NULL
                    )
                    SQL
                );
                $session->executeStatement(
                    <<<SQL
                    alter table transaction_test
                        add constraint transaction_test_foo
                        unique (foo)
                        deferrable
                    SQL
                );
                $session->executeStatement(
                    <<<SQL
                    alter table transaction_test
                        add constraint transaction_test_bar
                        unique (bar)
                        deferrable
                    SQL
                );
                break;
        }

        $session
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
        $session = $this->getDatabaseSession();
        $transaction = $session->beginTransaction();

        $session
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([4, 'd'])
            ->executeStatement()
        ;

        $transaction->commit();

        $result = $session
            ->select('transaction_test')
            ->orderBy('foo')
            ->executeQuery()
        ;

        // @todo Row count doesn't work with SQLite and SQLServer
        if ($this->ifDatabaseNot([Vendor::SQLITE, Vendor::SQLSERVER])) {
            self::assertSame(4, $result->rowCount());
        }
        self::assertSame('a', $result->fetchRow()->get('bar'));
        self::assertSame('b', $result->fetchRow()->get('bar'));
        self::assertSame('c', $result->fetchRow()->get('bar'));
        self::assertSame('d', $result->fetchRow()->get('bar'));
    }

    public function testNestedTransactionCreatesSavepoint()
    {
        $session = $this->getDatabaseSession();

        /* if (!$session->getPlatform()->supportsTransactionSavepoints()) {
            self::markTestSkipped(\sprintf("Driver '%s' does not supports savepoints", $session->getDriverName()));
        } */

        $session->delete('transaction_test')->executeStatement();

        $transaction = $session->beginTransaction();

        $session
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([789, 'f'])
            ->executeStatement()
        ;

        $savepoint = $session->beginTransaction();

        self::assertInstanceOf(TransactionSavepoint::class, $savepoint);
        self::assertTrue($savepoint->isNested());
        self::assertNotNull($savepoint->getSavepointName());

        $session
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([456, 'g'])
            ->executeStatement()
        ;

        $transaction->commit();

        $result = $session
            ->select('transaction_test')
            ->orderBy('foo')
            ->executeQuery()
        ;

        // @todo Row count doesn't work with SQLite and SQLServer
        if ($this->ifDatabaseNot([Vendor::SQLITE, Vendor::SQLSERVER])) {
            self::assertSame(2, $result->rowCount());
        }
        self::assertSame('g', $result->fetchRow()->get('bar'));
        self::assertSame('f', $result->fetchRow()->get('bar'));
    }

    public function testNestedTransactionRollbackToSavepointTransparently()
    {
        $session = $this->getDatabaseSession();

        /* if (!$session->getPlatform()->supportsTransactionSavepoints()) {
            self::markTestSkipped(\sprintf("Driver '%s' does not supports savepoints", $session->getDriverName()));
        } */

        $session->delete('transaction_test')->executeStatement();

        $transaction = $session->beginTransaction();

        $session
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([789, 'f'])
            ->executeStatement()
        ;

        $savepoint = $session->beginTransaction();

        self::assertInstanceOf(TransactionSavepoint::class, $savepoint);
        self::assertTrue($savepoint->isNested());
        self::assertNotNull($savepoint->getSavepointName());

        $session
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([456, 'g'])
            ->executeStatement()
        ;

        $savepoint->rollback();
        $transaction->commit();

        $result = $session
            ->select('transaction_test')
            ->orderBy('foo')
            ->executeQuery()
        ;

        // @todo Row count doesn't work with SQLite and SQLServer
        if ($this->ifDatabaseNot([Vendor::SQLITE, Vendor::SQLSERVER])) {
            self::assertSame(1, $result->rowCount());
        }
        self::assertSame('f', $result->fetchRow()->get('bar'));
    }

    /**
     * Fail with immediate constraints (not deferred).
     */
    public function testImmediateTransactionFail()
    {
        // @todo Support IMMEDIATE in the BEGIN statement for SQLite.
        self::skipIfDatabase(Vendor::SQLITE);
        self::skipIfDatabase(Vendor::SQLSERVER, 'SQL Server can not deffer constraints');

        self::expectNotToPerformAssertions();

        $session = $this->getDatabaseSession();

        $transaction = $session
            ->beginTransaction()
            ->deferred() // Defer all
            ->immediate('transaction_test_bar')
        ;

        try {
            // This should pass, foo constraint it deferred;
            // if backend does not support defering, this will
            // fail anyway, but the rest of the test is still
            // valid
            $session
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->executeStatement()
            ;

            // This should fail, bar constraint it immediate
            $session
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->executeStatement()
            ;

            self::fail();

        } catch (UniqueConstraintViolationError $e) {
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
        // @todo Support IMMEDIATE in the BEGIN statement for SQLite.
        self::skipIfDatabase(Vendor::SQLITE);
        self::skipIfDatabase(Vendor::SQLSERVER, 'SQL Server can not deffer constraints');

        self::expectNotToPerformAssertions();

        $session = $this->getDatabaseSession();

        /* if (!$session->getPlatform()->supportsDeferingConstraints()) {
            self::markTestSkipped("driver does not support defering constraints");
        } */

        $transaction = $session
            ->beginTransaction()
            ->immediate() // Immediate all
            ->deferred('transaction_test_foo')
        ;

        try {

            // This should pass, foo constraint it deferred
            $session
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->executeStatement()
            ;

            // This should fail, bar constraint it immediate
            $session
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->executeStatement()
            ;

            self::fail();

        } catch (UniqueConstraintViolationError $e) {
        } finally {
            if ($transaction->isStarted()) {
                $transaction->rollback();
            }
        }
    }

    /**
     * Fail with ALL constraints deferred.
     */
    public function testDeferredAllTransactionFail()
    {
        self::expectNotToPerformAssertions();

        $session = $this->getDatabaseSession();

        /* if (!$session->getPlatform()->supportsDeferingConstraints()) {
            self::markTestSkipped("driver does not support defering constraints");
        } */

        $transaction = $session
            ->beginTransaction()
            ->deferred()
        ;

        try {

            // This should pass, all are deferred
            $session
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->executeStatement()
            ;

            // This should pass, all are deferred
            $session
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->executeStatement()
            ;

            $transaction->commit();

        } catch (UniqueConstraintViolationError $e) {
        } finally {
            if ($transaction->isStarted()) {
                $transaction->rollback();
            }
        }
    }

    /**
     * Tests that rollback works.
     */
    public function testTransactionRollback()
    {
        $session = $this->getDatabaseSession();

        $transaction = $session->beginTransaction();

        $session
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([4, 'd'])
            ->executeStatement()
        ;

        $transaction->rollback();

        $result = $session
            ->select('transaction_test')
            ->executeQuery()
        ;

        // @todo Row count doesn't work with SQLite and SQLServer
        if ($this->ifDatabaseNot([Vendor::SQLITE, Vendor::SQLSERVER])) {
            self::assertSame(3, $result->rowCount());
        } else {
            $count = 0;
            foreach ($result as $row) {
                $count++;
            }
            self::assertSame(3, $count);
        }
    }

    /**
     * Test that fetching a pending transaction is disallowed.
     */
    public function testPendingAllowed()
    {
        $session = $this->getDatabaseSession();

        $transaction = $session->beginTransaction();

        // Fetch another transaction, it should fail
        try {
            $session->beginTransaction(Transaction::REPEATABLE_READ, false);
            self::fail();
        } catch (TransactionError $e) {
        }

        // Fetch another transaction, it should NOT fail
        $t3 = $session->beginTransaction(Transaction::REPEATABLE_READ, true);
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
        $session = $this->getDatabaseSession();

        $transaction = $session->beginTransaction();

        $session
            ->update('transaction_test')
            ->set('bar', 'z')
            ->where('foo', 1)
            ->executeStatement()
        ;

        $transaction->savepoint('bouyaya');

        $session
            ->update('transaction_test')
            ->set('bar', 'y')
            ->where('foo', 2)
            ->executeStatement()
        ;

        $transaction->rollbackToSavepoint('bouyaya');
        $transaction->commit();

        $oneBar = $session
            ->select('transaction_test')
            ->column('bar')
            ->where('foo', 1)
            ->executeQuery()
            ->fetchOne()
        ;
        // This should have pass since it's before the savepoint
        self::assertSame('z', $oneBar);

        $twoBar = $session
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
