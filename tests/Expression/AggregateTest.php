<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Expression\Aggregate;
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\Comparison;
use MakinaCorpus\QueryBuilder\Expression\Window;
use MakinaCorpus\QueryBuilder\Query\Query;
use MakinaCorpus\QueryBuilder\Query\Partial\OrderByStatement;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class AggregateTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Aggregate('foo', new ColumnName('bla'));

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Aggregate(
            'foo',
            new ColumnName('bla'),
            new Comparison(new ColumnName('bla'), new ColumnName('bar'), '='),
            new Window()
        );
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testColumn(): void
    {
        $expression = new Aggregate('foo', new ColumnName('bla'));

        self::assertSameSql(
            <<<SQL
            "foo"("bla")
            SQL,
            $expression
        );
    }

    public function testColumnFilter(): void
    {
        $expression = new Aggregate(
            'foo',
            new ColumnName('bla'),
            new Comparison(new ColumnName('a'), new ColumnName('b'), '='),
        );

        self::assertSameSql(
            <<<SQL
            "foo"("bla") filter (where "a" = "b")
            SQL,
            $expression
        );
    }

    public function testColumnFilterWhere(): void
    {
        $where = new Where();
        $where->isEqual(new ColumnName('a'), new ColumnName('b'));

        $expression = new Aggregate(
            'foo',
            new ColumnName('bla'),
            new Comparison(new ColumnName('a'), new ColumnName('b'), '='),
        );

        self::assertSameSql(
            <<<SQL
            "foo"("bla") filter (where "a" = "b")
            SQL,
            $expression
        );
    }

    public function testColumnOver(): void
    {
        $where = new Where();
        $where->isEqual(new ColumnName('a'), new ColumnName('b'));

        $expression = new Aggregate(
            'foo',
            new ColumnName('bla'),
            null,
            new Window(
                [new OrderByStatement("pouet", Query::ORDER_DESC, Query::NULL_IGNORE)],
            ),
        );

        self::assertSameSql(
            <<<SQL
            "foo"("bla") over (order by "pouet" desc)
            SQL,
            $expression
        );
    }

    public function testColumnOverFilter(): void
    {
        $where = new Where();
        $where->isEqual(new ColumnName('a'), new ColumnName('b'));

        $expression = new Aggregate(
            'foo',
            new ColumnName('bla'),
            new Comparison(new ColumnName('a'), new ColumnName('b'), '='),
            new Window(
                [new OrderByStatement("pouet", Query::ORDER_DESC, Query::NULL_IGNORE)],
            ),
        );

        self::assertSameSql(
            <<<SQL
            "foo"("bla") filter(where "a" = "b") over (order by "pouet" desc)
            SQL,
            $expression
        );
    }
}
