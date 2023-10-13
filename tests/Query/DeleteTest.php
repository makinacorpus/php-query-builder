<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Query;

use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Query\Delete;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

/**
 * It does not test all possibles variations of Where/With since they are done
 * with SelectTest, which in the end goes to generic implementations.
 */
class DeleteTest extends UnitTestCase
{
    public function testClone()
    {
        $query = new Delete('d');
        $query
            ->with('sdf', new ConstantTable([[1, 2]]))
            ->from('a')
            ->from('b')
            ->join('c')
            ->where('foo', 42)
        ;

        $cloned = clone $query;

        self::assertSameSql(
            $query,
            $cloned
        );
    }

    public function testReturns(): void
    {
        $query = new Delete('a');

        self::assertFalse($query->returns());

        $query->returning();

        self::assertTrue($query->returns());
    }

    public function testMultipleJoin(): void
    {
        $query = new Delete('bar');
        $query->join("foo", 'a = b');
        $query->join("fizz", 'buzz');

        self::assertSameSql(
            <<<SQL
            delete from "bar"
            using "foo"
            inner join "fizz"
                on (buzz)
            where
                ((a = b))
            SQL,
            $query
        );
    }

    public function testEmptyDelete()
    {
        $query = new Delete('some_table');

        self::assertSameSql(
            <<<SQL
            delete from "some_table"
            SQL,
            $query
        );
    }

    public function testWhere()
    {
        $query = new Delete('some_table');

        $query->where('a', 12);

        self::assertSameSql(
            <<<SQL
            delete from "some_table"
            where
                ("a" = #1)
            SQL,
            $query
        );
    }

    public function testExpression()
    {
        $query = new Delete('some_table');

        $query->whereRaw('true');

        self::assertSameSql(
            <<<SQL
            delete from "some_table"
            where
                (true)
            SQL,
            $query
        );
    }

    public function testReturningStar()
    {
        $query = new Delete('some_table');

        $query->returning();

        self::assertSameSql(
            <<<SQL
            delete from "some_table"
            returning
                *
            SQL,
            $query
        );
    }

    public function testReturningColumn()
    {
        $query = new Delete('some_table');

        $query->returning('a');

        self::assertSameSql(
            <<<SQL
            delete from "some_table"
            returning
                "a"
            SQL,
            $query
        );
    }

    public function testRetuningColumnWithAlias()
    {
        $query = new Delete('some_table');

        $query->returning('foo.a');

        self::assertSameSql(
            <<<SQL
            delete from "some_table"
            returning
                "foo"."a"
            SQL,
            $query
        );
    }

    public function testReturningRaw()
    {
        $query = new Delete('some_table');

        $query->returning(new Raw('a + 2'), 'a_plus_two');

        self::assertSameSql(
            <<<SQL
            delete from "some_table"
            returning
                a + 2 as "a_plus_two"
            SQL,
            $query
        );
    }
}
