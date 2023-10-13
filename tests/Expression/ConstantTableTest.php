<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class ConstantTableTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new ConstantTable(['bla']);

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new ConstantTable(['bla']);
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testEmpty(): void
    {
        $expression = new ConstantTable();

        foreach ($expression->getRows() as $row) {
            // @codeCoverageIgnore
            self::fail();
        }
        self::assertNull($expression->getColumns());

        self::assertSameSql(
            <<<SQL
            values ()
            SQL,
            $expression
        );
    }

    public function testArbitrary(): void
    {
        $expression = new ConstantTable([
            [1, 2, 'foo'],
            [3, 7, 'bla'],
            [23, 34234, 'yolo'],
        ]);

        self::assertSameSql(
            <<<SQL
            values
                (#1, #2, #3),
                (#4, #5, #6),
                (#7, #8, #9)
            SQL,
            $expression
        );
    }

    public function testGenerator(): void
    {
        $expression = new ConstantTable(
            function () {
                yield [1, 2];
                yield [2, 3];
            }
        );

        self::assertSameSql(
            <<<SQL
            values
                (#1, #2),
                (#3, #4)
            SQL,
            $expression
        );
    }

    public function testGeneratorThatDoesNotReturnIterableFails(): void
    {
        $expression = new ConstantTable(
            fn () => "This shall not pass"
        );

        self::expectExceptionMessageMatches('/Rows initializer is not iterable/');
        foreach ($expression->getRows() as $row) {}
    }

    public function testAddRow(): void
    {
        $expression = new ConstantTable([
            [1, 2, 'foo'],
        ]);

        $expression->row([1, 2, 'bar']);

        self::assertSameSql(
            <<<SQL
            values
                (#1, #2, #3),
                (#4, #5, #6)
            SQL,
            $expression
        );
    }

    public function testColumnCountMismatchRaiseError(): void
    {
        $expression = new ConstantTable([
            [1, 2, 'foo'],
            [3, 7],
        ]);

        self::expectExceptionMessageMatches('/Row at index #1 column count 2 mismatches from constant table column count 3/');
        foreach ($expression->getRows() as $row) {}
    }

    public function testSetColumnCountMismatchRaiseError(): void
    {
        $expression = new ConstantTable([
            [1, 2, 'foo'],
        ]);

        $expression->columns(['foo', 'bar']);

        self::expectExceptionMessageMatches('/Row at index #0 column count 3 mismatches from constant table column count 2/');
        foreach ($expression->getRows() as $row) {}
    }
}
