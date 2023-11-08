<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\RandomInt;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class RandomIntTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new RandomInt(1, 2);

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new RandomInt(1, 2);
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testBasic(): void
    {
        $expression = new RandomInt(7, 11);

        $prepared = static::createTestWriter()->prepare($expression);

        self::assertSameSql(
            <<<SQL
            floor(random() * (cast(#1 as int) - #2 + 1) + #3)
            SQL,
            $prepared
        );

        self::assertSame(
            [11, 7, 7],
            $prepared->getArguments()->getAll(),
        );
    }

    public function testInversed(): void
    {
        $expression = new RandomInt(11, 7);

        $prepared = static::createTestWriter()->prepare($expression);

        self::assertSameSql(
            <<<SQL
            floor(random() * (cast(#1 as int) - #2 + 1) + #3)
            SQL,
            $prepared
        );

        self::assertSame(
            [11, 7, 7],
            $prepared->getArguments()->getAll(),
        );
    }
}
