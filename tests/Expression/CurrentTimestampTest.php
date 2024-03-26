<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Expression;

use MakinaCorpus\QueryBuilder\Expression\CurrentTimestamp;
use MakinaCorpus\QueryBuilder\Tests\UnitTestCase;

class CurrentTimestampTest extends UnitTestCase
{
    public function testReturns(): void
    {
        $expression = new CurrentTimestamp();

        self::assertTrue($expression->returns());
    }

    public function testClone(): void
    {
        $expression = new CurrentTimestamp();
        $clone = clone $expression;

        self::assertEquals($expression, $clone);
    }

    public function testArbitrary(): void
    {
        $expression = new CurrentTimestamp();

        self::assertSameSql(
            <<<SQL
            current_timestamp
            SQL,
            $expression
        );
    }
}
