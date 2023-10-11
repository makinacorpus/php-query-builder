<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Expression\Raw;

class WhereTest extends AbstractWriterTestCase
{
    public function testReturns(): void
    {
        $where = new Where();
        self::assertTrue($where->returns());
    }

    public function testIsEmpty(): void
    {
        $where = new Where();
        self::assertTrue($where->isEmpty());

        $where->raw('true');
        self::assertFalse($where->isEmpty());
    }

    public function testRaw(): void
    {
        $where = new Where();
        $where->raw('do something with ?', 'this');

        self::assertSameSql(
            <<<SQL
            do something with #1
            SQL,
            $where
        );
    }

    public function testAddMultiple(): void
    {
        $where = new Where();
        $where->with(new Raw('me'), new Raw('you'), new Raw('him'));

        self::assertSameSql(
            <<<SQL
            me and you and him
            SQL,
            $where
        );
    }

    public function testNestedWithExpressions(): void
    {
        $where = new Where();
        $where->raw('bwa');
        $where->nested('or', new Raw('bwe'), new Raw('miaw'));

        self::assertSameSql(
            <<<SQL
            bwa and (
                bwe or miaw
            )
            SQL,
            $where
        );
    }
}
