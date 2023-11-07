<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Expression\CaseWhen;
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class CaseWhenTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new CaseWhen();

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new CaseWhen(new Value(1));
        $expression->add(new ColumnName('foo'), 2);
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testEmptyIsNull(): void
    {
        $expression = new CaseWhen();

        self::assertSameSql(
            <<<SQL
            null
            SQL,
            $expression
        );
    }

    public function testEmptyWithElse(): void
    {
        $expression = new CaseWhen(new Raw('foo()'));

        self::assertSameSql(
            <<<SQL
            foo()
            SQL,
            $expression
        );
    }

    public function testNoElseIsNull(): void
    {
        $expression = new CaseWhen();
        $expression->add('foo.bar', 'bla');

        self::assertSameSql(
            <<<SQL
            case when "foo"."bar" then #1 else null end
            SQL,
            $expression
        );
    }

    public function testWithWhere(): void
    {
        $expression = new CaseWhen(14);
        $expression->add(
            (new Where())->isEqual('foo.bar', 12),
            13,
        );

        self::assertSameSql(
            <<<SQL
            case when "foo"."bar" = #1 then #2 else #3 end
            SQL,
            $expression
        );
    }
}
