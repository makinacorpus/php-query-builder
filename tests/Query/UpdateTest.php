<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Query;

use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Query\Update;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

/**
 * It does not test all possibles variations of Where/With since they are done
 * with SelectTest, which in the end goes to generic implementations.
 */
class UpdateTest extends UnitTestCase
{
    public function testClone(): void
    {
        $query = new Update('a');
        $query->from('b');
        $query->join('c');
        $query->set('foo', 'bar');
        $query->createWith('bar', 'baz');

        $cloned = clone $query;

        self::assertSameSql(
            $query,
            $cloned
        );
    }

    public function testReturns(): void
    {
        $query = new Update('a');
        $query->set('b', 1);

        self::assertFalse($query->returns());

        $query->returning();

        self::assertTrue($query->returns());
    }

    public function testNoSetRaiseError(): void
    {
        $query = new Update('bar');

        self::expectExceptionMessageMatches('/without any columns to update/');
        self::createTestWriter()->prepare($query);
    }

    public function testSetWithTableNameRaiseError(): void
    {
        $query = new Update('bar');

        self::expectExceptionMessageMatches('/without table prefix/');
        $query->set('bar.foo', 1);
    }

    public function testJoinNested(): void
    {
        $query = new Update('bar');
        $query->set('foo', 1);
        $query->join(new Select('foo'), 'a = b', 'foo_2');
        $query->join("fizz", 'buzz');

        self::assertSameSql(
            <<<SQL
            update "bar"
            set
                "foo" = #1
            from (
                select * from "foo"
            ) as "foo_2"
            inner join "fizz"
                on (buzz)
            where
                ((a = b))
            SQL,
            $query
        );
    }

    public function testMultipleJoin(): void
    {
        $query = new Update('bar');
        $query->set('foo', 1);
        $query->join("foo", 'a = b');
        $query->join("fizz", 'buzz');

        self::assertSameSql(
            <<<SQL
            update "bar"
            set
                "foo" = #1
            from "foo"
            inner join "fizz"
                on (buzz)
            where
                ((a = b))
            SQL,
            $query
        );
    }

    public function testReturning(): void
    {
        $query = new Update('bar');
        $query->set('foo', 1);
        $query->returning('foo');
        $query->returning('fizz', 'buzz');

        self::assertSameSql(
            <<<SQL
            update "bar"
            set
                "foo" = #1
            returning
                "foo",
                "fizz" as "buzz"
            SQL,
            $query
        );
    }

    public function testSetUpdate(): void
    {
        $query = new Update('a');
        $query->sets([
            'fizz' => 42,
            'buzz' => 666,
        ]);
        $query->set('foo', 'bar');

        self::assertSameSql(
            <<<SQL
            update "a"
            set
                "fizz" = #1,
                "buzz" = #2,
                "foo" = #3
            SQL,
            $query
        );
    }

    public function testSetWithExpression(): void
    {
        $query = new Update('a');
        $query->set('foo', new Raw('bla()'));

        self::assertSameSql(
            <<<SQL
            update "a"
            set
                "foo" = bla()
            SQL,
            $query
        );
    }
}
