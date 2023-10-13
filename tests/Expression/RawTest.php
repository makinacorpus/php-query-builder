<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\ConstantTable;
use MakinaCorpus\QueryBuilder\Expression\Raw;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class RawTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Raw('arbitrary expression', ['bla']);

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Raw('arbitrary expression', ['bla']);
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testArbitraryPlaceholderIsUnescaped(): void
    {
        self::assertSameSql(
            <<<SQL
            select ?
            SQL,
            new Raw('select ??'),
        );
    }

    public function testArgumentsArePropagated(): void
    {
        $expression = new Raw(
            'select * from ? where ?',
            [
                new ConstantTable([[1, 'foo']]),
                'some value',
            ]
        );

        $result = self::createTestWriter()->prepare($expression);

        self::assertSame(
            [
                1,
                'foo',
                'some value',
            ],
            $result->getArguments()->getAll(),
        );

        self::assertSameSql(
            <<<SQL
            select * from
            values
                (#1, #2)
            where
                #3
            SQL,
            $result,
        );
    }
}
