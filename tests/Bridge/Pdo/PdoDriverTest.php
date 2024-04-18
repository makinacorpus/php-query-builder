<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Bridge\Pdo;

use MakinaCorpus\QueryBuilder\Expression\RandomInt;

class PdoDriverTest extends PdoTestCase
{
    public function testSelectExecuteQuery(): void
    {
        self::assertSame(
            1,
            (int) $this
                ->getDatabaseSession()
                ->select()
                ->columnRaw('1', 'foo')
                ->executeQuery()
                ->fetch(\PDO::FETCH_ASSOC)['foo']
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

        $actual = (int) $query->executeQuery()->fetchFirstColumn();

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
}
