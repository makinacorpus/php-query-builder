<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;
use MakinaCorpus\QueryBuilder\Type\Type;

class ValueTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Value('bla', 'varchar');

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Value('bla', 'varchar');
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testArbitrary(): void
    {
        $expression = new Value('bla', 'varchar');

        self::assertTrue($expression->returns());
        self::assertSame('bla', $expression->getValue());
        self::assertSameType(Type::varchar(), $expression->getType());

        self::assertSameSql(
            <<<SQL
            #1
            SQL,
            $expression
        );
    }

    public function testWithoutType(): void
    {
        $expression = new Value('foo');

        self::assertTrue($expression->returns());
        self::assertSame('foo', $expression->getValue());
        self::assertNull($expression->getType());

        self::assertSameSql(
            <<<SQL
            #1
            SQL,
            $expression
        );
    }

    public function testWithCastToType(): void
    {
        $expression = new Value('foo', null, 'varchar');

        self::assertTrue($expression->returns());
        self::assertSame('foo', $expression->getValue());
        self::assertNull($expression->getType());
        self::assertSameType(Type::varchar(), $expression->getCastToType());

        self::assertSameSql(
            <<<SQL
            cast(#1 as varchar)
            SQL,
            $expression
        );
    }
}
