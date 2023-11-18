<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Window;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Query\Partial\OrderByStatement;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class WindowTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Window(null, new ColumnName('foo'));

        self::assertFalse($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Window(null, new ColumnName('foo'));
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testEmpty(): void
    {
        $expression = new Window();

        self::assertSameSql(
            <<<SQL
            ()
            SQL,
            $expression
        );
    }

    public function testOrderBy(): void
    {
        $expression = new Window([
            new OrderByStatement('foo', Query::ORDER_ASC, Query::NULL_FIRST),
        ]);

        self::assertSameSql(
            <<<SQL
            (order by "foo" asc nulls first)
            SQL,
            $expression
        );
    }

    public function testOrderByMultiple(): void
    {
        $expression = new Window([
            new OrderByStatement('foo', Query::ORDER_ASC, Query::NULL_FIRST),
            new OrderByStatement('bla', Query::ORDER_DESC, Query::NULL_IGNORE),
        ]);

        self::assertSameSql(
            <<<SQL
            (order by "foo" asc nulls first, "bla" desc)
            SQL,
            $expression
        );
    }

    public function testPartitionBy(): void
    {
        $expression = new Window(null, new ColumnName("some_column"));

        self::assertSameSql(
            <<<SQL
            (partition by "some_column")
            SQL,
            $expression
        );
    }

    public function testPartitionByOrderBy(): void
    {
        $expression = new Window(
            [
                new OrderByStatement('foo', Query::ORDER_ASC, Query::NULL_FIRST),
            ],
            new Raw("foo()"),
        );

        self::assertSameSql(
            <<<SQL
            (partition by foo() order by "foo" asc nulls first)
            SQL,
            $expression
        );
    }

    public function testInAggregate(): void
    {
        self::markTestIncomplete("implement me");
    }

    public function testInSelect(): void
    {
        self::markTestIncomplete("implement me");
    }
}
