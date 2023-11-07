<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Where;
use MakinaCorpus\QueryBuilder\Expression\IfThen;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class IfThenTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new IfThen('bla', 1);

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new IfThen(new Value(1), null);
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testNoElseIsNull(): void
    {
        $expression = new IfThen('foo.bar', 'foo');

        self::assertSameSql(
            <<<SQL
            case when "foo"."bar" then #1 else null end
            SQL,
            $expression
        );
    }

    public function testWithWhere(): void
    {
        $expression = new IfThen(
            (new Where())->isEqual('foo.bar', 12),
            12,
            new Raw('foo()')
        );

        self::assertSameSql(
            <<<SQL
            case when "foo"."bar" = #1 then #2 else foo() end
            SQL,
            $expression
        );
    }
}
