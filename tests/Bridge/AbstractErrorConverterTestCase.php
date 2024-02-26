<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Bridge;

use MakinaCorpus\QueryBuilder\Platform;
use MakinaCorpus\QueryBuilder\Error\Bridge\AmbiguousIdentifierError;
use MakinaCorpus\QueryBuilder\Error\Bridge\ColumnDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Bridge\ForeignKeyConstraintViolationError;
use MakinaCorpus\QueryBuilder\Error\Bridge\TableDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\Bridge\UniqueConstraintViolationError;
use MakinaCorpus\QueryBuilder\Tests\FunctionalTestCase;
use MakinaCorpus\QueryBuilder\Transaction\Transaction;

abstract class AbstractErrorConverterTestCase extends FunctionalTestCase
{
    /** @before */
    protected function createSchema(): void
    {
        try {
            $this->getBridge()->executeStatement(
                <<<SQL
                DROP TABLE foo
                SQL
            );
        } catch (\Throwable) {}

        try {
            $this->getBridge()->executeStatement(
                <<<SQL
                DROP TABLE bar
                SQL
            );
        } catch (\Throwable) {}

        switch ($this->getBridge()->getServerFlavor()) {

            case Platform::MARIADB:
            case Platform::MYSQL:
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE foo (
                        id int UNIQUE NOT NULL,
                        name varchar(255) UNIQUE NOT NULL,
                        date datetime DEFAULT now()
                    )
                    SQL
                );
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE bar (
                        id int UNIQUE NOT NULL,
                        foo_id INT DEFAULT NULL,
                        data text DEFAULT NULL,
                        CONSTRAINT bar_foo_id_fk FOREIGN KEY (foo_id)
                            REFERENCES bar (id)
                            ON DELETE CASCADE
                    )
                    SQL
                );
                break;

            case Platform::SQLSERVER:
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE foo (
                        id int UNIQUE NOT NULL,
                        name nvarchar(500) UNIQUE NOT NULL,
                        date datetime2(6) DEFAULT current_timestamp
                    )
                    SQL
                );
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE bar (
                        id int UNIQUE NOT NULL,
                        foo_id INT DEFAULT NULL,
                        data nvarchar(500) DEFAULT NULL,
                        CONSTRAINT bar_foo_id_fk FOREIGN KEY (foo_id)
                            REFERENCES bar (id)
                            ON DELETE CASCADE
                    )
                    SQL
                );
                break;

            default:
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE foo (
                        id int UNIQUE NOT NULL,
                        name text UNIQUE NOT NULL,
                        date timestamp DEFAULT current_timestamp
                    )
                    SQL
                );
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE bar (
                        id int UNIQUE NOT NULL,
                        foo_id INT DEFAULT NULL,
                        data text DEFAULT NULL,
                        CONSTRAINT bar_foo_id_fk FOREIGN KEY (foo_id)
                            REFERENCES bar (id)
                            ON DELETE CASCADE
                    )
                    SQL
                );
                break;
        }
    }

    public function testAmbiguousIdentifierError(): void
    {
        self::expectException(AmbiguousIdentifierError::class);

        $this->getBridge()->executeStatement(
            <<<SQL
            SELECT id FROM foo, bar;
            SQL
        );
    }

    public function testConstraintViolationError(): void
    {
        self::markTestIncomplete("Not implemented yet.");
    }

    public function testDatabaseObjectDoesNotExistsError(): void
    {
        self::markTestIncomplete("Not implemented yet.");
    }

    public function testColumnDoesNotExistsError(): void
    {
        self::expectException(ColumnDoesNotExistError::class);

        $this->getBridge()->executeStatement(
            <<<SQL
            SELECT this_column_does_not_exist FROM foo;
            SQL
        );
    }

    public function testForeignKeyConstraintViolationError(): void
    {
        self::expectException(ForeignKeyConstraintViolationError::class);

        $this->getBridge()->executeStatement(
            <<<SQL
            INSERT INTO bar (id, foo_id) VALUES (12, 34);
            SQL
        );
    }

    public function testTableDoesNotExistError(): void
    {
        self::expectException(TableDoesNotExistError::class);

        $this->getBridge()->executeStatement(
            <<<SQL
            SELECT * FROM this_table_does_not_exist;
            SQL
        );
    }

    public function testUniqueConstraintViolationError(): void
    {
        $this->getBridge()->executeStatement(
            <<<SQL
            INSERT INTO foo (id, name) VALUES (1, 'foo');
            SQL
        );

        self::expectException(UniqueConstraintViolationError::class);

        $this->getBridge()->executeStatement(
            <<<SQL
            INSERT INTO foo (id, name) VALUES (2, 'foo');
            SQL
        );
    }

    /**
     * Scenario here comes from official PostgreSQL documentation.
     *
     * @link https://www.postgresql.org/docs/10/transaction-iso.html#XACT-SERIALIZABLE
     */
    public function testTransactionSerializationError1(): void
    {
        self::markTestIncomplete("This test requires two different connections, this is unhanlded yet.");

        $bridge = $this->getBridge();

        if (!$runner1->getPlatform()->supportsSchema()) {
            self::markTestSkipped("This test requires a schema.");
        }
        self::markTestIncomplete("Why does the heck it does not fail?");

        $runner2 = $factory->getRunner();

        $runner1->execute(
            <<<SQL
            DROP TABLE IF EXISTS public.test_transaction_1
            SQL
        );

        $runner1->execute(
            <<<SQL
            CREATE TABLE IF NOT EXISTS public.test_transaction_1 (class int, value int)
            SQL
        );

        $runner1->execute(
            <<<SQL
            INSERT INTO public.test_transaction_1 (class, value)
            VALUES (
                1, 10
            ), (
                1, 20
            ), (
                2, 100
            ), (
                2, 200
            )
            SQL
        );

        // Default level is REPEATABLE READ.
        $transaction1 = $runner1->beginTransaction(Transaction::SERIALIZABLE);
        $transaction2 = $runner2->beginTransaction(Transaction::SERIALIZABLE);

        $runner1->execute(
            <<<SQL
            SELECT SUM(value) FROM public.test_transaction_1 WHERE class = 1;
            SQL
        );

        $runner1->execute(
            <<<SQL
            INSERT INTO public.test_transaction_1 (class, value) VALUES (2, 30)
            SQL
        );

        $runner2->execute(
            <<<SQL
            SELECT SUM(value) FROM public.test_transaction_1 WHERE class = 2;
            SQL
        );

        $runner2->execute(
            <<<SQL
            INSERT INTO public.test_transaction_1 (class, value) VALUES (1, 300)
            SQL
        );

        $transaction1->commit();
        $transaction2->commit();
    }

    public function testTransactionSerializationError2(): void
    {
        self::markTestIncomplete("This test requires two different connections, this is unhanlded yet.");

        $runner1 = $factory->getRunner();

        if (!$runner1->getPlatform()->supportsSchema()) {
            self::markTestSkipped("This test requires a schema.");
        }
        self::markTestIncomplete("This test requires that we send a query batch asynchronously in the second transaction.");

        $runner2 = $factory->getRunner();

        $runner1->execute(
            <<<SQL
            DROP TABLE IF EXISTS public.test_transaction_2
            SQL
        );

        $runner1->execute(
            <<<SQL
            CREATE TABLE IF NOT EXISTS public.test_transaction_2 (id int PRIMARY KEY)
            SQL
        );

        // Default level is REPEATABLE READ.
        $transaction1 = $runner1->beginTransaction(Transaction::SERIALIZABLE);
        $transaction2 = $runner2->beginTransaction(Transaction::SERIALIZABLE);

        $runner1->execute(
            <<<SQL
            INSERT INTO public.test_transaction_2 (id) VALUES (1)
            SQL
        );

        $runner2->execute(
            <<<SQL
            INSERT INTO public.test_transaction_2 (id) VALUES (1)
            SQL
        );

        $transaction1->commit();
        $transaction2->commit();
    }

    public function testTransactionDeadlockError(): void
    {
        self::markTestIncomplete("Not implemented yet.");
    }

    public function testTransactionWaitTimeoutError(): void
    {
        self::markTestIncomplete("Not implemented yet.");
    }
}
