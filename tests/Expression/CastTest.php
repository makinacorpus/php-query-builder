<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Cast;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Row;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class CastTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Cast(new Raw('foo'), 'int');

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Cast(new Raw('foo'), 'bar');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testWithArbitraryValue(): void
    {
        $expression = new Cast("1234", 'int', 'string');

        self::assertSameSql(
            <<<SQL
            cast(#1 as int)
            SQL,
            $expression
        );
    }

    public function testWithRow(): void
    {
        $expression = new Cast(new Row([1, 2, 3]), 'some_type');

        self::assertSameSql(
            <<<SQL
            cast(row(#1, #2, #3) as some_type)
            SQL,
            $expression
        );
    }

    public function testWithAliasAndParenthesis(): void
    {
        self::markTestIncomplete('There is no parenthesis friendly expressions yet');

        /*
        $expression = new Cast(new Raw('foo'), 'alias');

        self::assertSameSql(
            <<<SQL
            cast((foo) as int)
            SQL,
            $expression
        );
         */
    }
}
