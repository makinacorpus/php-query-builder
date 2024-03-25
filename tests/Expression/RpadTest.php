<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Expression\Rpad;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;
use MakinaCorpus\QueryBuilder\Type\Type;

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

        self::assertSameType(Type::text(), $expression->returnType());
    }

    public function testClone(): void
    {
        $expression = new Rpad('foo', 12);
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testCastPerDefault(): void
    {
        $expression = new Rpad('foo', 12);

        self::assertSameSql(
            <<<SQL
            rpad(cast(#1 as varchar), cast(#2 as int), cast(#3 as varchar))
            SQL,
            $expression
        );
    }

    public function testCastIfValueWithoutTypes(): void
    {
        $expression = new Rpad(new Value("'foo'"), new Value("12", 'int'), new Value("' '", 'text'));

        self::assertSameSql(
            <<<SQL
            rpad(cast(#1 as varchar), cast(#2 as int), cast(#3 as varchar))
            SQL,
            $expression
        );
    }

    public function testDoesNotCastWhenRawOrColumn(): void
    {
        $expression = new Rpad(new Raw("'foo'"), new Raw("12"), new ColumnName('foo.bar'));

        self::assertSameSql(
            <<<SQL
            rpad('foo', 12, "foo"."bar")
            SQL,
            $expression
        );
    }
}
