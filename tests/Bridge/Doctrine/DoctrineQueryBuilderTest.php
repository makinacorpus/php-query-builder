<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine;

class DoctrineQueryBuilderTest extends DoctrineTestCase
{
    public function testSelectExecuteQuery(): void
    {
        self::assertSame(
            1,
            (int) $this
                ->getQueryBuilder()
                ->select()
                ->columnRaw('1')
                ->executeQuery()
                ->fetchOne()
        );
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
