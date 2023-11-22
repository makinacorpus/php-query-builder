<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Rpad;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class RpadTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Rpad('foo', 12);

        self::assertTrue($expression->returns());
    }

    public function testReturnType(): void
    {
        $expression = new Rpad('foo', 12);

        self::assertSame('text', $expression->returnType());
    }

    public function testClone(): void
    {
        $expression = new Rpad('foo', 12);
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testBasic(): void
    {
        $expression = new Rpad('foo', 12);

        self::assertSameSql(
            <<<SQL
            rpad(#1, #2, #3)
            SQL,
            $expression
        );
    }

    public function testCastIfNotTypes(): void
    {
        $expression = new Rpad(new Raw("'foo'"), new Raw("12"), new Raw("' '"));

        self::assertSameSql(
            <<<SQL
            rpad(cast('foo' as text), cast(12 as int), cast(' ' as text))
            SQL,
            $expression
        );
    }
}
