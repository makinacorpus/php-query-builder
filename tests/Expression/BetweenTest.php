<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\Between;
use MakinaCorpus\QueryBuilder\Expression\ColumnName;
use MakinaCorpus\QueryBuilder\Expression\Value;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class BetweenTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new Between(new ColumnName('foo'), new Value(1), new Value(2));

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new Between(new ColumnName('foo'), new Value(1), new Value(2));
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testBasic(): void
    {
        $expression = new Between(new ColumnName('foo'), new Value(1), new Value(2));

        self::assertSameSql(
            <<<SQL
            "foo" between #1 and #2
            SQL,
            $expression
        );
    }
}
