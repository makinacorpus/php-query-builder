<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Expression\Raw;

class WhereTest extends UnitTestCase
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

    public function testNesting(): void
    {
        $where = new Where();
        $where
            ->nested('XOR')
                ->isEqual('foo', 'bar')
                ->isBetween('bla', 1, 2)
            ->end()
            ->and()
                ->isNotNull('fizz')
                ->isGreater('bla', 12)
            ->end()
            ->or()
                ->isLessOrEqual('buzz', 13)
                ->isGreaterOrEqual('miaw', 231)
            ->end()
            ->isNotEqual('bwaa', 4958)
        ;

        self::assertSameSql(
            <<<SQL
            (
                "foo" = #1
                xor "bla" between #2 and #3
            )
            and (
                "fizz" is not null
                and "bla" > #4
            )
            and (
                "buzz" <= #5
                or "miaw" >= #6
            )
            and "bwaa" <> #7
            SQL,
            $where
        );
    }

    public function testNestingNesting(): void
    {
        $where = new Where();
        $where
            ->or()
                ->and()
                    ->isNotNull('fizz')
                    ->isGreater('bla', 12)
                ->end() // Here we loose parent autocompletion, sadly.
                ->isBetween('bla', 1, 2)
            ->end()
            ->isNotEqual('bwaa', 4958)
        ;

        self::assertSameSql(
            <<<SQL
            (
                (
                    "fizz" is not null
                    and "bla" > #1
                )
                or "bla" between #2 and #3
            )
            and "bwaa" <> #4
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
