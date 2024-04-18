<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Not;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class NotTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Not(new Raw('foo'));

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Not(new Raw('foo'));
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testNot(): void
    {
        $expression = new Not(new Raw('foo'));

        self::assertSameSql(
            <<<SQL
            not foo
            SQL,
            $expression
        );
    }

    public function testNotWithParenthesis(): void
    {
        self::markTestIncomplete('There is no parenthesis friendly expressions yet');

        /*
        $expression = new Not(new Raw('foo'));

        self::assertSameSql(
            <<<SQL
            not (...)
            SQL,
            $expression
        );
         */
    }
}
