<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\ArrayValue;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Tests\AbstractWriterTestCase;

class ArrayValueTest extends AbstractWriterTestCase
{
    public function testReturns(): void
    {
        $expression = new ArrayValue(['test']);

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new ArrayValue(['test']);
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testValueType(): void
    {
        $expression = new ArrayValue([1, 2], 'int');

        self::assertSame('int', $expression->getValueType());
    }

    public function testFormatWithCast(): void
    {
        $expression = new ArrayValue([7, 13], 'int');

        self::assertSameSql(
            <<<SQL
            CAST(ARRAY[#1, #2] AS int[])
            SQL,
            $expression
        );
    }

    public function testFormatWithoutCast(): void
    {
        $expression = new ArrayValue([7, 13], 'int', false);

        self::assertSameSql(
            <<<SQL
            ARRAY[#1, #2]
            SQL,
            $expression
        );
    }

    public function testValueConversionToExpression(): void
    {
        $expression = new ArrayValue([7, 13]);

        $count = 0;
        foreach  ($expression->getValues() as $value) {
            self::assertInstanceOf(Value::class, $value);
            $count++;
        }
        self::assertSame(2, $count);
    }
}
