<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine;

use MakinaCorpus\QueryBuilder\Expression\RandomInt;

class DoctrineBridgeTest extends DoctrineTestCase
{
    public function testSelectExecuteQuery(): void
    {
        self::assertSame(
            1,
            (int) $this
                ->getDatabaseSession()
                ->select()
                ->columnRaw('1')
                ->executeQuery()
                ->fetchOne()
        );
    }

    /**
     * This test comes from a use case of makinacorpus/db-tools-bundle where
     * PostgreSQL driver was unable to guess data type of some values, causing
     * exception to rise.
     */
    public function testParameterTypeGuess(): void
    {
        $query = $this
            ->getDatabaseSession()
            ->raw(
                'select ?',
                [
                    new RandomInt(9999),
                ],
            )
        ;

        $actual = (int) $query->executeQuery()->fetchOne();

        self::assertLessThan(10000, $actual);
        self::assertGreaterThan(0, $actual);
    }

    public function testDeleteExecuteStatement(): void
    {
        self::markTestIncomplete("Write me");
    }

    public function testMergeExecuteStatement(): void
    {
        self::markTestIncomplete("Write me");
    }

    public function testUpdateExecuteStatement(): void
    {
        self::markTestIncomplete("Write me");
    }

    public function testInsertExecuteStatement(): void
    {
        self::markTestIncomplete("Write me");
    }

    public function testCloseConnect(): void
    {
        $session = $this->getDatabaseSession();

        self::assertSame(7, (int) $session->executeQuery('SELECT 7')->fetchOne());

        $session->close();
        $session->connect();

        self::assertSame(11, (int) $session->executeQuery('SELECT 11')->fetchOne());
    }
}
