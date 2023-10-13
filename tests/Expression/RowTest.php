<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Row;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class RowTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Row(['foo', 'bar']);

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Row(['foo', 'bar']);
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testEmpty(): void
    {
        $expression = new Row([]);

        foreach ($expression->getValues() as $row) {
            self::fail();
        }
        self::assertTrue($expression->returns());

        self::assertSameSql(
            <<<SQL
            ()
            SQL,
            $expression
        );
    }

    public function testSingleValue(): void
    {
        $expression = new Row('foo');

        self::assertSameSql(
            <<<SQL
            (#1)
            SQL,
            $expression
        );
    }

    public function testArbitrary(): void
    {
        $expression = new Row([1, 2, 'foo']);

        self::assertSameSql(
            <<<SQL
            (#1, #2, #3)
            SQL,
            $expression
        );
    }

    public function testGenerator(): void
    {
        $expression = new Row(
            function () {
                yield 1;
                yield 2;
                yield 3;
            }
        );

        self::assertSameSql(
            <<<SQL
            (#1, #2, #3)
            SQL,
            $expression
        );
    }

    public function testGeneratorThatDoesNotReturnIterableIsConvertedToArray(): void
    {
        $expression = new Row(
            fn () => "This shall not pass"
        );

        self::assertSameSql(
            <<<SQL
            (#1)
            SQL,
            $expression
        );
    }

    public function testWithCompositeType(): void
    {
        $expression = new Row([1, 2, 'foo'], 'some_type');

        self::assertSameSql(
            <<<SQL
            cast ((#1, #2, #3) as some_type)
            SQL,
            $expression
        );
    }
}
