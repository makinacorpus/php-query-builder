<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\NullValue;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class NullValueTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new NullValue();

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new NullValue();
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testArbitrary(): void
    {
        $expression = new NullValue();

        self::assertSameSql(
            <<<SQL
            null
            SQL,
            $expression
        );
    }
}
