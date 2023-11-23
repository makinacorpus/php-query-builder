<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Lpad;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class LpadTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Lpad('foo', 12);

        self::assertTrue($expression->returns());
    }

    public function testReturnType(): void
    {
        $expression = new Lpad('foo', 12);

        self::assertSame('varchar', $expression->returnType());
    }

    public function testClone(): void
    {
        $expression = new Lpad('foo', 12);
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testBasic(): void
    {
        $expression = new Lpad('foo', 12);

        self::assertSameSql(
            <<<SQL
            lpad(#1, #2, #3)
            SQL,
            $expression
        );
    }

    public function testCastIfNotTypes(): void
    {
        $expression = new Lpad(new Raw("'foo'"), new Raw("12"), new Raw("' '"));

        self::assertSameSql(
            <<<SQL
            lpad(cast('foo' as varchar), cast(12 as int), cast(' ' as varchar))
            SQL,
            $expression
        );
    }
}
