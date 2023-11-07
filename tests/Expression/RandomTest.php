<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Random;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class RandomTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Random();

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Random();
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testBasic(): void
    {
        $expression = new Random();

        self::assertSameSql(
            <<<SQL
            random()
            SQL,
            $expression
        );
    }
}
