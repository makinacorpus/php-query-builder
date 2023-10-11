<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Query;

use MakinaCorpus\QueryBuilder\Query\RawQuery;
use MakinaCorpus\QueryBuilder\Tests\AbstractWriterTestCase;

class RawTest extends AbstractWriterTestCase
{
    public function testClone(): void
    {
        $query = new RawQuery('select ?', 'foo');
        $cloned = clone $query;

        self::assertSameSql(
            $query,
            $cloned
        );
    }

    public function testReturns(): void
    {
        $query = new RawQuery('select ?', 'foo');

        self::assertTrue($query->returns());
    }

    public function testArguments(): void
    {
        $query = new RawQuery('select ?', 'foo');

        self::assertSameSql(
            <<<SQL
            select #1
            SQL,
            $query
        );
    }
}
